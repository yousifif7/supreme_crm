<!DOCTYPE html>
<html>
<head>
    <title>User Heatmap</title>
    <style>
        #map { height: 100vh; width: 100%; }
    </style>
</head>
<body>
    <div id="map"></div>

    <script>
        const userId = {{ $userId }};
        let map;
        let heatmap;

        function initMap() {
            const mapDiv = document.getElementById('map');

            // Temporary center until data loads
            map = new google.maps.Map(mapDiv, {
                center: { lat: 0, lng: 0 },
                zoom: 12
            });

            // Fetch last hour locations
            fetch(`/locations/latest/${userId}`)
                .then(res => res.json())
                .then(locations => {
                    if (!locations.length) return;

                    const bounds = new google.maps.LatLngBounds();
                    const heatPoints = locations.map(loc => {
                        const latLng = new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude));
                        bounds.extend(latLng);
                        return latLng;
                    });

                    heatmap = new google.maps.visualization.HeatmapLayer({
                        data: heatPoints,
                        radius: 15,
                        opacity: 0.8,
                        map: map
                    });

                    map.fitBounds(bounds);
                });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=visualization&callback=initMap" async defer></script>
</body>
</html>
