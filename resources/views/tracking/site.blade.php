<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Site Tracking</title>
  <style>
    html, body { height: 100%; margin: 0; padding: 0; }
    #site-map { width: 100%; height: 70vh; border: 1px solid #e6e6e6; }
    .controls { padding: 0.75rem; display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; }
    .small-muted { color: #6c757d; font-size:0.9rem; }
  </style>
</head>
<body>
  <div class="controls">
    <button id="refresh-btn">Refresh</button>
    <label class="small-muted">Auto refresh
      <input type="checkbox" id="auto-refresh" checked style="margin-left:6px">
    </label>
    <div id="last-updated" class="small-muted" style="margin-left:12px">Initializing…</div>
    <div id="status" class="small-muted" style="margin-left:auto"></div>
  </div>

  <div id="site-map"></div>

  <script>
    // Blade will substitute the values server-side
    const SITE_ID = "{{ $siteId ?? '' }}";
    const DATA_URL = "{{ url('/track/site') }}/" + encodeURIComponent(SITE_ID) + "/data";
    const GM_API_KEY = "{{ env('GOOGLE_MAPS_API_KEY') }}";
    // Optional: controller may pass `siteAddress` or `siteZip` to help center the map
    const SITE_QUERY = @json($siteAddress ?? $siteZip ?? '');

    function extractSiteQueryFromData(json) {
      if (!json) return '';
      // common possible field names coming from server
      const keys = ['siteAddress', 'site_address', 'address', 'postal_code', 'post_code', 'postcode', 'zip'];
      for (const k of keys) {
        if (json[k] && String(json[k]).trim()) return String(json[k]).trim();
      }
      // nested site object?
      if (json.site) return extractSiteQueryFromData(json.site);
      // some endpoints return metadata top-level
      if (json.meta) return extractSiteQueryFromData(json.meta);
      return '';
    }

    let siteMarker = null;
    let siteCircle = null;

    function geocodeAndCenter(query) {
      return new Promise((resolve) => {
        if (!query || !window.google || !google.maps) return resolve(null);
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: query }, (results, status) => {
          if (status === 'OK' && results && results[0] && results[0].geometry && results[0].geometry.location) {
            try {
              const loc = results[0].geometry.location;
              map.setCenter(loc);
              map.setZoom(15);
              return resolve(loc);
            } catch (e) {
              console.warn('Failed to set center from geocode result', e);
            }
          } else {
            console.warn('Geocode not OK for SITE_QUERY:', status, query);
          }
          resolve(null);
        });
      });
    }

    function drawSiteZone(latLng, options = {}) {
      const radius = options.radiusMeters || 100; // default 100m
      const title = options.title || 'Site Boundary';

      // clear existing
      if (siteMarker) { siteMarker.setMap(null); siteMarker = null; }
      if (siteCircle) { siteCircle.setMap(null); siteCircle = null; }

      siteMarker = new google.maps.Marker({
        position: latLng,
        map: map,
        title: options.title || 'Site',
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
        map: map,
        center: latLng,
        radius: radius
      });

      // attach info window to marker
      const iw = new google.maps.InfoWindow({ content: `<div style="min-width:200px"><strong>${options.title||'Site'}</strong><div style="font-size:0.9rem;color:#666">${options.address||''}</div></div>` });
      siteMarker.addListener('click', () => iw.open(map, siteMarker));
    }

    let map;
    let markers = [];
    let circles = [];
    let infoWindow;
    let autoRefreshInterval = null;

    function loadGoogleMaps(callbackName = 'initSiteMap') {
      return new Promise((resolve, reject) => {
        if (window.google && window.google.maps) return resolve();
        if (document.getElementById('gm-site-maps')) {
          const check = () => {
            if (window.google && window.google.maps) resolve();
            else setTimeout(check, 150);
          };
          return check();
        }
        window[callbackName] = () => resolve();
        const src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(GM_API_KEY)}&callback=${callbackName}`;
        const s = document.createElement('script');
        s.id = 'gm-site-maps';
        s.async = true;
        s.defer = true;
        s.src = src;
        s.onerror = () => reject(new Error('Failed to load Google Maps API'));
        document.head.appendChild(s);
      });
    }

    function parseTimestamp(raw) {
      if (!raw) return null;
      // if numeric string determine seconds vs ms
      if (/^\d+$/.test(String(raw))) {
        const num = Number(raw);
        if (num > 1e12) return new Date(num);       // ms
        if (num > 1e9) return new Date(num * 1000); // seconds
      }
      const d = new Date(raw);
      if (!isNaN(d)) return d;
      return null;
    }

    function clearOverlays() {
      markers.forEach(m => m.setMap(null));
      circles.forEach(c => c.setMap(null));
      markers = [];
      circles = [];
    }

    function createMarkerLabel(name) {
      if (!name) return '';
      // create short label from initials
      const parts = name.trim().split(/\s+/).filter(Boolean);
      if (!parts.length) return '';
      if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
      return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
    }

    async function fetchAndRender() {
      const statusEl = document.getElementById('status');
      const lastEl = document.getElementById('last-updated');

      statusEl.textContent = 'Loading…';
      try {
        const res = await fetch(DATA_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        const locations = Array.isArray(json.locations) ? json.locations : [];

        clearOverlays();

        if (!locations.length) {
          statusEl.textContent = 'No locations available';
          lastEl.textContent = `Last updated: ${new Date().toLocaleString()}`;
          return;
        }

        // Build bounds
        const bounds = new google.maps.LatLngBounds();
        let firstLatLng = null;

        locations.forEach(loc => {
          const lat = Number(loc.latitude);
          const lng = Number(loc.longitude);
          if (!isFinite(lat) || !isFinite(lng)) return;
          const pos = new google.maps.LatLng(lat, lng);
          if (!firstLatLng) firstLatLng = pos;
          bounds.extend(pos);

          // marker
          const label = {
            text: createMarkerLabel(loc.name ?? ''),
            color: 'white',
            fontSize: '12px',
            fontWeight: 'bold'
          };
          const marker = new google.maps.Marker({
            position: pos,
            map: map,
            title: loc.name || `User ${loc.user_id}`,
            label: label,
            // small circle icon fallback if you prefer: use default marker for clarity
          });

          // accuracy circle
          if (loc.accuracy && Number(loc.accuracy) > 0) {
            const circle = new google.maps.Circle({
              strokeColor: '#007bff',
              strokeOpacity: 0.35,
              strokeWeight: 1,
              fillColor: '#007bff',
              fillOpacity: 0.12,
              map: map,
              center: pos,
              radius: Number(loc.accuracy) // meters
            });
            circles.push(circle);
          }

          // info window
          const ts = parseTimestamp(loc.timestamp);
          const tsText = ts ? ts.toLocaleString() : (loc.timestamp || 'N/A');
          const content =
            `<div style="min-width:150px">
               <div style="font-weight:600">${loc.name ?? 'Unknown'}</div>
               <div style="font-size:0.9rem;color:#666">Accuracy: ${loc.accuracy ?? 'N/A'} m</div>
               <div style="font-size:0.85rem;color:#666">Time: ${tsText}</div>
             </div>`;
          marker.addListener('click', () => {
            if (!infoWindow) infoWindow = new google.maps.InfoWindow();
            infoWindow.setContent(content);
            infoWindow.open(map, marker);
          });

          markers.push(marker);
        });

        // fit or center
        if (!firstLatLng) {
          statusEl.textContent = 'No valid coordinates';
        } else if (markers.length === 1) {
          map.setCenter(firstLatLng);
          map.setZoom(16);
        } else {
          try {
            map.fitBounds(bounds, 40);
          } catch (e) {
            console.warn('fitBounds failed', e);
          }
        }

        statusEl.textContent = `Showing ${markers.length} location(s)`;
        lastEl.textContent = `Last updated: ${new Date().toLocaleString()}`;
      } catch (err) {
        console.error(err);
        statusEl.textContent = 'Failed to load locations';
      }
    }

    function setupControls() {
      document.getElementById('refresh-btn').addEventListener('click', () => fetchAndRender());
      const auto = document.getElementById('auto-refresh');
      auto.addEventListener('change', function () {
        if (this.checked) {
          if (autoRefreshInterval) clearInterval(autoRefreshInterval);
          autoRefreshInterval = setInterval(fetchAndRender, 30000);
        } else {
          if (autoRefreshInterval) { clearInterval(autoRefreshInterval); autoRefreshInterval = null; }
        }
      });
      // start auto-refresh if checked
      if (auto.checked) autoRefreshInterval = setInterval(fetchAndRender, 30000);
    }

    async function initMap() {
      try {
        await loadGoogleMaps('initSiteMap');
      } catch (err) {
        document.getElementById('status').textContent = 'Maps API failed to load. Check key/billing.';
        console.error(err);
        return;
      }

      map = new google.maps.Map(document.getElementById('site-map'), {
        center: { lat: 51.5074, lng: -0.1278 },
        zoom: 12,
        gestureHandling: 'greedy',
        mapTypeId: 'roadmap'
      });

      setupControls();

      // Try to geocode SITE_QUERY first; if not provided or geocoding fails, attempt to derive from DATA_URL payload
      let loc = null;
      let siteMeta = null;

      if (SITE_QUERY && SITE_QUERY.length) {
        loc = await geocodeAndCenter(SITE_QUERY);
      }

      if (!loc) {
        // fetch site data which may contain address/postcode metadata
        try {
          const res = await fetch(DATA_URL, { cache: 'no-store' });
          if (res && res.ok) {
            const json = await res.json();
            siteMeta = json.site || null;
            const derived = extractSiteQueryFromData(json) || '';
            if (derived) {
              loc = await geocodeAndCenter(derived);
            }
          }
        } catch (e) {
          console.warn('Failed to fetch site data for geocoding', e);
        }
      }

      // If we found a geocoded location, draw a zone around it
      if (loc) {
        drawSiteZone(loc, {
          radiusMeters: (siteMeta && siteMeta.radius_meters) ? siteMeta.radius_meters : 100,
          title: (siteMeta && siteMeta.site_name) ? siteMeta.site_name : (SITE_QUERY || 'Site'),
          address: (siteMeta && siteMeta.address) ? siteMeta.address : (SITE_QUERY || ''),
        });
      }

      // finally load markers (will fit bounds if not centered above)
      await fetchAndRender();
    }

    // initialize
    initMap();
  </script>
</body>
</html>