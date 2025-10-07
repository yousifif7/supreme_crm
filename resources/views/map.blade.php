<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=visualization&callback=initMap" async defer></script>

<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    #map {
        height: 350px;
        width: 100%;
    }
</style>

<div id="map"></div>

<script>
let map;
let heatmap;
let allHeatPoints = [];

// Fetch locations from backend and update heatmap
function fetchAndUpdateHeatmap() {
    fetch("{{ route('shift.locations', ['shiftDateId' => $shiftDate->id]) }}")
        .then(res => res.json())
        .then(data => {
            const locations = data.locations || [];
            allHeatPoints = []; // reset

            if (!locations.length) {
                console.warn("No location data found for this shift!");
                return;
            }

            const bounds = new google.maps.LatLngBounds();

            // Interpolated points
            for (let i = 0; i < locations.length - 1; i++) {
                const start = locations[i];
                const end = locations[i + 1];

                const latStep = (parseFloat(end.latitude) - parseFloat(start.latitude)) / 10;
                const lngStep = (parseFloat(end.longitude) - parseFloat(start.longitude)) / 10;

                for (let j = 0; j <= 10; j++) {
                    const lat = parseFloat(start.latitude) + latStep * j;
                    const lng = parseFloat(start.longitude) + lngStep * j;
                    const latLng = new google.maps.LatLng(lat, lng);
                    allHeatPoints.push(latLng);
                    bounds.extend(latLng);
                }
            }

            // Handle single location
            if (locations.length === 1) {
                const loc = locations[0];
                const latLng = new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude));
                allHeatPoints.push(latLng);
                bounds.extend(latLng);
            }

            // Fit map to new bounds
            map.fitBounds(bounds);

            // Update heatmap
            if (!heatmap) {
                heatmap = new google.maps.visualization.HeatmapLayer({
                    data: allHeatPoints,
                    radius: 20,
                    opacity: 0.8,
                    dissipating: true,
                    gradient: [
                        'rgba(0,0,0,0)',
                        'rgba(255,255,0,0.6)',
                        'rgba(255,165,0,0.7)',
                        'rgba(255,0,0,0.8)',
                        'rgba(128,0,0,1)'
                    ]
                });
                heatmap.setMap(map);
            } else {
                heatmap.setData(allHeatPoints);
            }
        })
        .catch(err => console.error("Heatmap fetch error:", err));
}

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 14,
        center: { lat: 51.5074, lng: -0.1278 }, // fallback center (London)
        mapTypeId: 'roadmap'
    });

    // Initial load
    fetchAndUpdateHeatmap();

    // Auto-refresh every 30 seconds
    setInterval(fetchAndUpdateHeatmap, 30000);
}
</script>
