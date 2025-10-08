<div id="gm-deck-wrapper" style="position:relative;">
  <div id="gm-deck-map" style="width:100%;height:420px;border:1px solid #e6e6e6"></div>

  <div class="mt-2 d-flex gap-2 align-items-center">
    <select id="gm-map-type" class="form-select form-select-sm" style="width:auto">
      <option value="roadmap">Roadmap</option>
      <option value="satellite" selected>Satellite</option>
      <option value="hybrid">Hybrid</option>
      <option value="terrain">Terrain</option>
    </select>

    <button id="gm-toggle-heat" class="btn btn-sm btn-outline-primary">Toggle Heat</button>
    <button id="gm-toggle-path" class="btn btn-sm btn-outline-secondary">Toggle Path</button>

    <label class="mb-0">Radius
      <input id="gm-radius" type="range" min="5" max="200" value="30" style="vertical-align:middle;margin-left:6px">
    </label>

    <label class="mb-0">Intensity
      <input id="gm-intensity" type="range" min="1" max="20" value="8" style="vertical-align:middle;margin-left:6px">
    </label>

    <div class="form-check form-check-inline ms-2">
      <input class="form-check-input" type="checkbox" id="gm-autorefresh" checked>
      <label class="form-check-label small" for="gm-autorefresh">Auto refresh</label>
    </div>

    <div id="gm-deck-status" class="small text-muted ms-3">Initializing map…</div>
  </div>
</div>

<script>
/*
  deck.gl + Google Maps overlay heatmap partial

  Requirements:
  - Your backend endpoint should return JSON:
    { shift: {...}, user: {...}, locations: [{ latitude, longitude, created_at }, ...], meta: {...} }
    (Your provided ShiftController::shiftLocations already does this.)
  - Put your Google Maps API key in .env as GOOGLE_MAPS_API_KEY
  - This partial uses the deck.gl UMD bundles from unpkg (no build step required)

  Notes:
  - deck.gl's HeatmapLayer expects coordinates as [lng, lat].
  - We show a polyline & markers with Google Maps (so links remain simple).
  - We use server-side sampling via ?max_points=... to avoid sending huge arrays.
*/

const DECK_LOCATIONS_URL = "{{ route('shift.locations', ['shiftDateId' => $shiftDate->id]) }}";
const GM_API_KEY = "{{ env('GOOGLE_MAPS_API_KEY') }}";

let gm_map, gm_deckOverlay, gm_pathPolyline, gm_startMarker, gm_endMarker;
let deckLoaded = false;
let deckOverlayActive = false;
let pathVisible = true;
const statusEl = document.getElementById('gm-deck-status');

function loadScriptOnce(src, id) {
  return new Promise((resolve, reject) => {
    if (document.getElementById(id)) {
      // already injected — wait until global is available
      const check = () => {
        if (window.deck && window.deck.GoogleMapsOverlay) resolve();
        else if (window.deck && id.indexOf('google-maps') === -1) resolve();
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

async function ensureDeckAndMaps() {
  // Load deck.gl UMD bundles (core + layers + google-maps integration)
  // Versions chosen are stable in the 8.x line; update if you manage dependencies.
  if (!window.deck) {
    await loadScriptOnce('https://unpkg.com/deck.gl@8.9.0/dist.min.js', 'deck-core');
  }
  if (!window.deck || !window.deck.GoogleMapsOverlay) {
    await loadScriptOnce('https://unpkg.com/@deck.gl/google-maps@8.9.0/dist.min.js', 'deck-google-maps');
  }
  deckLoaded = !!(window.deck && window.deck.GoogleMapsOverlay);
  return deckLoaded;
}

// Load Google Maps with no visualization library (we're using deck.gl rendering)
function loadGoogleMaps(callbackName = 'initDeckGmMap') {
  return new Promise((resolve, reject) => {
    if (window.google && window.google.maps) return resolve();

    // Avoid duplicating script
    if (document.getElementById('gm-maps-script')) {
      // the script will call window[callbackName]; we just resolve when google available
      const check = () => {
        if (window.google && window.google.maps) resolve();
        else setTimeout(check, 200);
      };
      return check();
    }

    // create global callback to resolve
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

  // create map immediately
  gm_map = new google.maps.Map(document.getElementById('gm-deck-map'), {
    center: { lat: 51.5074, lng: -0.1278 },
    zoom: 13,
    mapTypeId: 'satellite',
    gestureHandling: 'greedy'
  });

  // polyline rendered by Google Maps (path)
  gm_pathPolyline = new google.maps.Polyline({
    path: [],
    strokeColor: '#FF5722',
    strokeOpacity: 0.85,
    strokeWeight: 4,
    map: null
  });

  // ensure deck.gl is loaded
  try {
    const loaded = await ensureDeckAndMaps();
    if (!loaded) throw new Error('deck.gl missing');
  } catch (err) {
    console.warn('deck.gl load failed, heatmap will not be shown:', err);
    statusEl.textContent = 'deck.gl failed to load; showing path only';
    // Still fetch and show path & markers
    await refreshData({ useDeck: false });
    return;
  }

  // create deck GoogleMaps overlay (initially with empty layers)
  const { HeatmapLayer } = deck; // deck.gl exposes layers on the UMD bundle
  // GoogleMapsOverlay is available via deck.GoogleMapsOverlay or deck.GoogleMapsOverlay (UMD)
  const GoogleMapsOverlay = deck.GoogleMapsOverlay || deck.GoogleMapsOverlay;
  gm_deckOverlay = new GoogleMapsOverlay({
    layers: []
  });
  // attach overlay to map
  gm_deckOverlay.setMap(gm_map);
  deckOverlayActive = true;

  // initial data load
  await refreshData({ useDeck: true });

  // auto-refresh
  if (document.getElementById('gm-autorefresh').checked) {
    window._gm_deck_interval = setInterval(() => refreshData({ useDeck: true }), 30000);
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
    // Normalize into {lng, lat, weight?}
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

function buildDeckHeatLayer(points, radiusPixels = 30, intensity = 8) {
  // deck.gl HeatmapLayer expects objects with position: [lng,lat] or accessor for getPosition
  // instantiate using the deck.gl UMD global 'deck'
  const { HeatmapLayer } = deck;
  return new HeatmapLayer({
    id: 'deck-heat',
    data: points.map(p => ({ position: p.position, weight: 1 })),
    getPosition: d => d.position,
    getWeight: d => d.weight || 1,
    radiusPixels: radiusPixels,
    intensity: intensity,
    aggregation: 'SUM'
  });
}

async function refreshData({ useDeck = true } = {}) {
  const statusEl = document.getElementById('gm-deck-status');
  statusEl.textContent = 'Loading points…';
  const { pts, meta } = await fetchLocations(2000);
  statusEl.textContent = `${meta.returned ?? pts.length} points`;

  if (!pts.length) {
    // clear visuals
    if (gm_deckOverlay && deckOverlayActive) gm_deckOverlay.setProps({ layers: [] });
    gm_pathPolyline.setPath([]);
    if (gm_startMarker) { gm_startMarker.setMap(null); gm_startMarker = null; }
    if (gm_endMarker) { gm_endMarker.setMap(null); gm_endMarker = null; }
    return;
  }

  // show path on google map
  const path = pts.map(p => new google.maps.LatLng(p.latitude, p.longitude));
  gm_pathPolyline.setPath(path);
  if (pathVisible && !gm_pathPolyline.getMap()) gm_pathPolyline.setMap(gm_map);

  // start / end markers
  if (gm_startMarker) { gm_startMarker.setMap(null); gm_startMarker = null; }
  if (gm_endMarker) { gm_endMarker.setMap(null); gm_endMarker = null; }
  const first = pts[0], last = pts[pts.length - 1];
  gm_startMarker = new google.maps.Marker({
    position: { lat: first.latitude, lng: first.longitude },
    map: gm_map,
    title: 'Start',
    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6, fillColor: '#28a745', fillOpacity: 1, strokeWeight: 0 }
  });
  gm_endMarker = new google.maps.Marker({
    position: { lat: last.latitude, lng: last.longitude },
    map: gm_map,
    title: 'End',
    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6, fillColor: '#dc3545', fillOpacity: 1, strokeWeight: 0 }
  });

  // fit bounds
  if (pts.length === 1) {
    gm_map.setCenter({ lat: first.latitude, lng: first.longitude });
    gm_map.setZoom(16);
  } else {
    const bounds = new google.maps.LatLngBounds();
    pts.forEach(p => bounds.extend(new google.maps.LatLng(p.latitude, p.longitude)));
    try { gm_map.fitBounds(bounds); } catch (e) { /* ignore */ }
  }

  // deck overlay heatmap
  if (useDeck && deckLoaded && gm_deckOverlay) {
    // get UI values
    const radius = parseInt(document.getElementById('gm-radius').value, 10) || 30;
    const intensity = parseInt(document.getElementById('gm-intensity').value, 10) || 8;
    // build heat layer
    const heatLayer = buildDeckHeatLayer(pts, radius, intensity);
    gm_deckOverlay.setProps({ layers: [heatLayer] });
  } else {
    // fallback: clear deck layers
    if (gm_deckOverlay) gm_deckOverlay.setProps({ layers: [] });
  }
}

// Controls wiring
document.getElementById('gm-map-type').addEventListener('change', (e) => gm_map.setMapTypeId(e.target.value));
document.getElementById('gm-toggle-heat').addEventListener('click', function () {
  if (!gm_deckOverlay) return;
  const visible = !!(gm_deckOverlay.props && gm_deckOverlay.props.layers && gm_deckOverlay.props.layers.length);
  if (visible) gm_deckOverlay.setProps({ layers: [] });
  else refreshData({ useDeck: true });
  this.textContent = visible ? 'Show Heat' : 'Hide Heat';
});
document.getElementById('gm-toggle-path').addEventListener('click', function () {
  pathVisible = !pathVisible;
  if (pathVisible) gm_pathPolyline.setMap(gm_map);
  else gm_pathPolyline.setMap(null);
  this.textContent = pathVisible ? 'Hide Path' : 'Show Path';
});
document.getElementById('gm-radius').addEventListener('input', () => refreshData({ useDeck: deckLoaded }));
document.getElementById('gm-intensity').addEventListener('input', () => refreshData({ useDeck: deckLoaded }));

// auto refresh toggle
document.getElementById('gm-autorefresh').addEventListener('change', function () {
  if (this.checked) {
    window._gm_deck_interval = setInterval(() => refreshData({ useDeck: deckLoaded }), 30000);
  } else {
    if (window._gm_deck_interval) clearInterval(window._gm_deck_interval);
  }
});

// initialize
initMapAndDeck();
</script>