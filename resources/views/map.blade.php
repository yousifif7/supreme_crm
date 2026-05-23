<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    #gm-deck-map {
        width: 100%;
    }
</style>

<div id="gm-deck-wrapper" style="position:relative;">
  <div id="gm-deck-map" style="width:100%;height:500px;border:1px solid #e6e6e6"></div>

  <div class="mt-2 d-flex gap-2 align-items-center">
    <select id="gm-map-type" class="form-select form-select-sm" style="width:auto">
      <option value="roadmap">Roadmap</option>
      <option value="satellite" selected>Satellite</option>
      <option value="hybrid">Hybrid</option>
      <option value="terrain">Terrain</option>
    </select>

    <button id="gm-toggle-path" class="btn btn-sm btn-outline-secondary">Toggle Path</button>
    
    <label class="mb-0">GPS Filter (m)
      <input id="gm-gps-filter" type="range" min="0" max="20" value="5" style="vertical-align:middle;margin-left:6px">
      <span id="gm-filter-value" class="small">5m</span>
    </label>
    <label class="mb-0">Min Time (min)
      <input id="gm-min-time" type="range" min="0" max="20" value="2" style="vertical-align:middle;margin-left:6px">
      <span id="gm-min-time-value" class="small">2m</span>
    </label>

    <div class="form-check form-check-inline ms-2">
      <input class="form-check-input" type="checkbox" id="gm-autorefresh" checked>
      <label class="form-check-label small" for="gm-autorefresh">Auto refresh</label>
    </div>
    
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" id="gm-smooth-path" checked>
      <label class="form-check-label small" for="gm-smooth-path">Smooth path</label>
    </div>

    <div id="gm-deck-status" class="small text-muted ms-3">Initializing map…</div>
  </div>
</div>

<script>
const DECK_LOCATIONS_URL = "{{ route('shift.locations', ['shiftDateId' => $shiftDate->id]) }}";
const GM_API_KEY = "{{ env('GOOGLE_MAPS_API_KEY') }}";
// Site metadata — prefer server-resolved coords (accurate) over client-side geocoding
const SITE_ADDRESS   = @json(trim($shiftDate->shift->site->address ?? ''));
const SITE_POSTCODE  = @json(trim($shiftDate->shift->site->post_code ?? ''));
const SITE_PLUS_CODE = @json(trim($shiftDate->shift->site->plus_code ?? ''));
const SITE_QUERY     = SITE_ADDRESS && SITE_POSTCODE
  ? SITE_ADDRESS + ' ' + SITE_POSTCODE
  : (SITE_ADDRESS || SITE_POSTCODE);
const SITE_TITLE = @json($shiftDate->shift->site->site_name ?? '');
const SITE_LAT = @json($siteLat ?? null);
const SITE_LNG = @json($siteLng ?? null);

let gm_map, gm_pathPolyline, gm_startMarker, gm_endMarker;
let pathVisible = true;
const statusEl = document.getElementById('gm-deck-status');

let siteMarker = null;
let siteCircle = null;

// 4-tier geocode strategy:
//   Tier 0: plus code (if stored) → highest precision, no country restriction needed
//   Tier 1: full address + GB restriction → accept only ROOFTOP / RANGE_INTERPOLATED
//   Tier 2: if imprecise (GEOMETRIC_CENTER / APPROXIMATE) → retry with postcode only
//   Tier 3: if full address call fails entirely → postcode only
function geocodeAndCenterSite(query) {
  return new Promise((resolve) => {
    if (!window.google || !google.maps) return resolve(null);
    const geocoder = new google.maps.Geocoder();
    const preciseTypes = ['ROOFTOP', 'RANGE_INTERPOLATED'];

    function applyResult(loc) {
      try { gm_map.setCenter(loc); gm_map.setZoom(17); } catch (e) { console.warn('setCenter failed', e); }
      return resolve(loc);
    }

    function tryPostcodeOnly() {
      if (!SITE_POSTCODE) return resolve(null);
      geocoder.geocode({ address: SITE_POSTCODE, componentRestrictions: { country: 'GB' } }, (r2, s2) => {
        if (s2 === 'OK' && r2 && r2[0]) return applyResult(r2[0].geometry.location);
        console.warn('Geocode tier 3 (postcode-only) failed', s2);
        resolve(null);
      });
    }

    function tryAddressQuery() {
      if (!query) return tryPostcodeOnly();
      // Tier 1: full query + GB restriction
      geocoder.geocode({ address: query, componentRestrictions: { country: 'GB' } }, (results, status) => {
        if (status === 'OK' && results && results[0]) {
          const locType = results[0].geometry.location_type;
          if (preciseTypes.includes(locType)) {
            return applyResult(results[0].geometry.location);
          }
          // Tier 2: imprecise result — retry with postcode only
          console.info('Geocode tier 1 imprecise (' + locType + '), retrying with postcode', SITE_POSTCODE);
          return tryPostcodeOnly();
        }
        // Tier 3: full query failed — fall back to postcode
        console.warn('Geocode tier 1 failed (' + status + '), falling back to postcode');
        tryPostcodeOnly();
      });
    }

    // Tier 0: plus code — most precise, try first
    if (SITE_PLUS_CODE) {
      geocoder.geocode({ address: SITE_PLUS_CODE }, (results, status) => {
        if (status === 'OK' && results && results[0]) {
          return applyResult(results[0].geometry.location);
        }
        console.info('Plus code geocode failed (' + status + '), falling back to address');
        tryAddressQuery();
      });
    } else {
      tryAddressQuery();
    }
  });
}

function drawSiteZoneOnMap(latLng, opts = {}) {
  const radius = opts.radiusMeters || 100;
  const title = opts.title || SITE_TITLE || 'Site';

  if (siteMarker) { siteMarker.setMap(null); siteMarker = null; }
  if (siteCircle) { siteCircle.setMap(null); siteCircle = null; }

  siteMarker = new google.maps.Marker({
    position: latLng,
    map: gm_map,
    title: title,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 6,
      fillColor: '#ff5722',
      fillOpacity: 1,
      strokeColor: '#fff',
      strokeWeight: 1
    }
  });

  siteCircle = new google.maps.Circle({
    strokeColor: '#ff5722',
    strokeOpacity: 0.6,
    strokeWeight: 2,
    fillColor: '#ffccbc',
    fillOpacity: 0.25,
    map: gm_map,
    center: latLng,
    radius: radius
  });

  const iw = new google.maps.InfoWindow({ content: `<div style="min-width:200px"><strong>${title}</strong><div style="font-size:0.9rem;color:#666">${opts.address||''}</div></div>` });
  siteMarker.addListener('click', () => iw.open(gm_map, siteMarker));
}

// ===== GPS FILTERING & SMOOTHING UTILITIES =====

// Haversine distance in meters
function haversineDistance(lat1, lng1, lat2, lng2) {
    const R = 6371000; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;
    
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// Filter GPS noise - remove points closer than threshold
function filterGPSNoise(points, minDistanceMeters = 5) {
  if (points.length === 0) return [];
  const filtered = [points[0]];
  for (let i = 1; i < points.length; i++) {
    const last = filtered[filtered.length - 1];
    const curr = points[i];
    const distance = haversineDistance(last.latitude, last.longitude, curr.latitude, curr.longitude);
    if (distance >= minDistanceMeters) {
      filtered.push(curr);
    }
  }
  return filtered;
}

// Filter by minimum time between points (in minutes)
function filterMinTime(points, minMinutes = 2) {
  if (points.length === 0 || minMinutes <= 0) return points;
  const filtered = [points[0]];
  let lastTime = parseTimestamp(points[0].raw);
  for (let i = 1; i < points.length; i++) {
    const currTime = parseTimestamp(points[i].raw);
    if (isNaN(currTime) || isNaN(lastTime)) {
      filtered.push(points[i]);
      lastTime = currTime;
      continue;
    }
    if ((currTime - lastTime) >= minMinutes * 60 * 1000) {
      filtered.push(points[i]);
      lastTime = currTime;
    }
  }
  return filtered;
}

// Catmull-Rom interpolation
function catmullRomInterpolate(p0, p1, p2, p3, t) {
    const t2 = t * t;
    const t3 = t2 * t;
    
    return 0.5 * (
        (2 * p1) +
        (-p0 + p2) * t +
        (2 * p0 - 5 * p1 + 4 * p2 - p3) * t2 +
        (-p0 + 3 * p1 - 3 * p2 + p3) * t3
    );
}

// Create smooth curve through GPS points
function createSmoothPath(points, segmentsPerPoint = 15) {
    if (points.length < 2) return points;
    if (points.length === 2) return points; // straight line
    
    const smoothed = [points[0]];
    
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = i > 0 ? points[i - 1] : points[i];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = i < points.length - 2 ? points[i + 2] : points[i + 1];
        
        // Adjust segments based on distance
        const distance = haversineDistance(p1.latitude, p1.longitude, p2.latitude, p2.longitude);
        let segments = segmentsPerPoint;
        
        if (distance < 2) segments = 2;
        else if (distance < 10) segments = 8;
        else if (distance > 50) segments = Math.min(30, Math.floor(distance / 2));
        
        for (let t = 1; t <= segments; t++) {
            const tt = t / segments;
            const lat = catmullRomInterpolate(
                p0.latitude, p1.latitude, p2.latitude, p3.latitude, tt
            );
            const lng = catmullRomInterpolate(
                p0.longitude, p1.longitude, p2.longitude, p3.longitude, tt
            );
            smoothed.push({ latitude: lat, longitude: lng });
        }
    }
    
    return smoothed;
}

function computeCentroid(points) {
  if (!points || points.length === 0) return null;
  let sumLat = 0, sumLng = 0;
  points.forEach(p => { sumLat += p.latitude; sumLng += p.longitude; });
  return { latitude: sumLat / points.length, longitude: sumLng / points.length };
}

// ===== EXISTING FUNCTIONS (updated) =====

function loadScriptOnce(src, id) {
  return new Promise((resolve, reject) => {
    if (document.getElementById(id)) {
      const check = () => {
        if (window.google && window.google.maps) resolve();
        else setTimeout(check, 100);
      };
      return check();
    }
    const s = document.createElement('script');
    s.id = id;
    s.async = true;
    s.defer = true;
    s.src = src;
    s.onload = () => resolve();
    s.onerror = (e) => reject(new Error('Failed to load ' + src));
    document.head.appendChild(s);
  });
}

function loadGoogleMaps(callbackName = 'initDeckGmMap') {
  return new Promise((resolve, reject) => {
    if (window.google && window.google.maps) return resolve();

    if (document.getElementById('gm-maps-script')) {
      const check = () => {
        if (window.google && window.google.maps) resolve();
        else setTimeout(check, 200);
      };
      return check();
    }

    window[callbackName] = () => resolve();
    const src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(GM_API_KEY)}&callback=${callbackName}`;
    const s = document.createElement('script');
    s.id = 'gm-maps-script';
    s.async = true;
    s.defer = true;
    s.src = src;
    s.onerror = () => reject(new Error('Failed to load Google Maps API'));
    document.head.appendChild(s);
  });
}

async function initMapAndDeck() {
  statusEl.textContent = 'Loading map & deck…';

  try {
    await loadGoogleMaps('initDeckGmMap');
  } catch (err) {
    statusEl.textContent = 'Maps API failed to load. Check key/billing.';
    console.error(err);
    return;
  }

  gm_map = new google.maps.Map(document.getElementById('gm-deck-map'), {
    center: { lat: 51.5074, lng: -0.1278 },
    zoom: 13,
    mapTypeId: 'roadmap',
    gestureHandling: 'greedy'
  });

  gm_pathPolyline = new google.maps.Polyline({
    path: [],
    strokeColor: '#FF5722',
    strokeOpacity: 0.85,
    strokeWeight: 4,
    geodesic: false, // Important for smooth curves
    map: null
  });

  // Draw site zone — use server-resolved coordinates when available (reliable),
  // otherwise fall back to client-side geocoding (may fail for business names).
  try {
    if (SITE_LAT !== null && SITE_LNG !== null) {
      const siteLoc = new google.maps.LatLng(SITE_LAT, SITE_LNG);
      try { gm_map.setCenter(siteLoc); gm_map.setZoom(17); } catch(e){}
      drawSiteZoneOnMap(siteLoc, { title: SITE_TITLE || 'Site', address: SITE_QUERY, radiusMeters: 100 });
    } else if (SITE_QUERY && SITE_QUERY.length) {
      const siteLoc = await geocodeAndCenterSite(SITE_QUERY);
      if (siteLoc) {
        drawSiteZoneOnMap(siteLoc, { title: SITE_TITLE || 'Site', address: SITE_QUERY, radiusMeters: 100 });
      }
    }
  } catch (e) {
    console.warn('Site geocode/draw failed', e);
  }

  await refreshData();

  if (document.getElementById('gm-autorefresh').checked) {
    window._gm_deck_interval = setInterval(() => refreshData(), 30000);
  }

  statusEl.textContent = 'Ready';
}

async function fetchLocations(maxPoints = 1500) {
  try {
    const url = new URL(DECK_LOCATIONS_URL, window.location.origin);
    url.searchParams.set('max_points', String(maxPoints));
    const res = await fetch(url.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
          console.log('Raw point:', json.locations);

    const pts = (json.locations || []).map(p => {
      const lat = parseFloat(p.latitude ?? p.lat ?? p.latitud ?? p.lat_val ?? null);
      const lng = parseFloat(p.longitude ?? p.lng ?? p.long ?? p.lng_val ?? null);
      if (!isFinite(lat) || !isFinite(lng)) return null;
      return { position: [lng, lat], latitude: lat, longitude: lng, raw: p };
    }).filter(Boolean);
    return { pts, meta: json.meta ?? {} };
  } catch (err) {
    console.error('fetchLocations error', err);
    return { pts: [], meta: {} };
  }
}

// Heatmap layer removed (deck.gl) — map now shows path only

function parseTimestamp(raw) {
  if (!raw) return NaN;
  const keys = ['created_at', 'timestamp', 'time', 'ts', 't'];
  for (const k of keys) {
    if (raw[k]) {
      const v = raw[k];
      const n = Date.parse(v);
      if (!isNaN(n)) return n;
      const num = Number(v);
      if (!isNaN(num)) {
        if (num > 1e12) return num;
        if (num > 1e9) return num * 1000;
      }
    }
  }
  return NaN;
}

function sortPointsByTime(points) {
  const withIdx = points.map((p, i) => ({ p, i }));
  withIdx.forEach(item => {
    item.t = parseTimestamp(item.p.raw) || item.i;
  });
  withIdx.sort((a, b) => a.t - b.t);
  return withIdx.map(x => x.p);
}

async function refreshData() {
  statusEl.textContent = 'Loading points…';
  const { pts, meta } = await fetchLocations(2000);
  
  if (!pts.length) {
    statusEl.textContent = 'No points found';
    gm_pathPolyline.setPath([]);
    if (gm_startMarker) { gm_startMarker.setMap(null); gm_startMarker = null; }
    if (gm_endMarker) { gm_endMarker.setMap(null); gm_endMarker = null; }
    return;
  }

  // Sort chronologically
  const sorted = sortPointsByTime(pts);
  
  // Get GPS filter threshold
  const filterThreshold = parseInt(document.getElementById('gm-gps-filter').value, 10) || 5;
  
  // Filter GPS noise
  const filteredGPS = filterGPSNoise(sorted, filterThreshold);
  // Filter by minimum time
  const minTime = parseInt(document.getElementById('gm-min-time').value, 10) || 0;
  const filtered = filterMinTime(filteredGPS, minTime);

  console.log('Original points:', pts.length);
  console.log('Sorted points:', sorted.length);
  console.log('Filtered (GPS):', filteredGPS.length);
  console.log('Filtered (Time):', filtered.length);

  statusEl.textContent = `${pts.length} pts → ${filtered.length} filtered`;

  // Check if smooth path is enabled
  const smoothEnabled = document.getElementById('gm-smooth-path').checked;

  // Create path (smooth or direct)
  let pathPts = filtered;
  if (smoothEnabled && filtered.length > 2) {
    pathPts = createSmoothPath(filtered, 15);
    console.log('Smoothed to:', pathPts.length, 'points');
  }

  // Draw path
  if (pathPts.length > 0) {
    const path = pathPts.map(p => new google.maps.LatLng(p.latitude, p.longitude));
    gm_pathPolyline.setPath(path);
    if (pathVisible && !gm_pathPolyline.getMap()) {
      gm_pathPolyline.setMap(gm_map);
    }
  }

  // Clear old markers
  if (gm_startMarker) { gm_startMarker.setMap(null); gm_startMarker = null; }
  if (gm_endMarker) { gm_endMarker.setMap(null); gm_endMarker = null; }

  // Add start/end markers (use filtered points, not smoothed)
  if (filtered.length > 0) {
    const first = filtered[0];
    const last = filtered[filtered.length - 1];

    gm_startMarker = new google.maps.Marker({
      position: { lat: first.latitude, lng: first.longitude },
      map: gm_map,
      title: 'Start',
      label: { text: 'START', color: '#FFF', fontWeight: 'bold', fontSize: '10px' },
      icon: {
        url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
        scaledSize: new google.maps.Size(40, 40)
      },
      zIndex: 1000
    });
    
    if (filtered.length > 1) {
      gm_endMarker = new google.maps.Marker({
        position: { lat: last.latitude, lng: last.longitude },
        map: gm_map,
        title: 'End',
        label: { text: 'END', color: '#FFF', fontWeight: 'bold', fontSize: '10px' },
        icon: {
          url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
          scaledSize: new google.maps.Size(40, 40)
        },
        zIndex: 1000
      });
    }
  }

  // Always keep the map centered on the site (set during geocodeAndCenterSite).
  // Do NOT fitBounds to guard locations — the site marker is the reference point.

  // No heatmap: path-only display
}

// ===== CONTROLS =====

document.getElementById('gm-map-type').addEventListener('change', (e) => {
  gm_map.setMapTypeId(e.target.value);
});

// Heat toggle removed

document.getElementById('gm-toggle-path').addEventListener('click', function () {
  pathVisible = !pathVisible;
  if (pathVisible) {
    gm_pathPolyline.setMap(gm_map);
    this.textContent = 'Hide Path';
  } else {
    gm_pathPolyline.setMap(null);
    this.textContent = 'Show Path';
  }
});

// Heat controls removed

document.getElementById('gm-gps-filter').addEventListener('input', function() {
  document.getElementById('gm-filter-value').textContent = this.value + 'm';
  refreshData();
});
document.getElementById('gm-min-time').addEventListener('input', function() {
  document.getElementById('gm-min-time-value').textContent = this.value + 'm';
  refreshData();
});

document.getElementById('gm-smooth-path').addEventListener('change', () => {
  refreshData();
});

document.getElementById('gm-autorefresh').addEventListener('change', function () {
  if (this.checked) {
    window._gm_deck_interval = setInterval(() => refreshData(), 30000);
  } else {
    if (window._gm_deck_interval) clearInterval(window._gm_deck_interval);
  }
});

// Lazy-init: only load the heavy Google Maps API + fetch points once the map container
// is actually scrolled into view. Saves a large script + ajax round-trip on initial load
// when the user doesn't immediately look at the map.
(function lazyInit() {
  const target = document.getElementById('gm-deck-map');
  if (!target) return;

  let started = false;
  const start = () => {
    if (started) return;
    started = true;
    initMapAndDeck();
  };

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          io.disconnect();
          start();
          break;
        }
      }
    }, { rootMargin: '200px' });
    io.observe(target);
  } else {
    // Older browsers: fall back to a short timeout so we don't block first paint.
    setTimeout(start, 500);
  }

  // Pause polling when the tab/window is hidden — saves Google Maps quota & bandwidth.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      if (window._gm_deck_interval) {
        clearInterval(window._gm_deck_interval);
        window._gm_deck_interval = null;
      }
    } else if (started && document.getElementById('gm-autorefresh')?.checked && !window._gm_deck_interval) {
      window._gm_deck_interval = setInterval(() => refreshData(), 30000);
    }
  });
})();
</script>