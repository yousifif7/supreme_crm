
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=visualization"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map {
            height: 500px;
            width: 500px;
        }
    </style>
    <div id="map"></div>

<script>
let map, heatmap;
let allHeatPoints = [];

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 14,
        center: { lat: 51.5074, lng: -0.1278 }, // fallback center
        mapTypeId: 'roadmap'
    });

    fetch("{{ route('shift.locations', ['shiftDateId' => $shiftDate->id]) }}")
        .then(res => res.json())
        .then(data => {
            const locations = data.locations || [];
            if (!locations.length) {
                alert("No location data for this shift.");
                return;
            }

            const bounds = new google.maps.LatLngBounds();

            locations.forEach(loc => {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                const latLng = new google.maps.LatLng(lat, lng);
                allHeatPoints.push(latLng);
                bounds.extend(latLng);
            });

            map.fitBounds(bounds);

            heatmap = new google.maps.visualization.HeatmapLayer({
                data: allHeatPoints,
                radius: 50,       // size of heat points
                opacity: 0.7,     // transparency
                dissipating: true,
                gradient: [
                    'rgba(0, 255, 255, 0)',
                    'rgba(0, 255, 255, 1)',
                    'rgba(0, 191, 255, 1)',
                    'rgba(0, 127, 255, 1)',
                    'rgba(0, 63, 255, 1)',
                    'rgba(0, 0, 255, 1)',
                    'rgba(63, 0, 255, 1)',
                    'rgba(127, 0, 255, 1)',
                    'rgba(191, 0, 255, 1)',
                    'rgba(255, 0, 255, 1)'
                ]
            });

            heatmap.setMap(map);

            document.getElementById("toggleHeatmap").addEventListener("click", () => {
                heatmap.setMap(heatmap.getMap() ? null : map);
            });
        })
        .catch(err => console.error(err));
}
window.onload = initMap;
</script>
