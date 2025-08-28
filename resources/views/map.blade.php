<!DOCTYPE html>
<html>
<head>
    <title>Shift Heatmap</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Maps API with your API key -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=visualization"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map {
            height: 100%;
            width: 100%;
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <script>
    function initMap() {
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 14,
            center: { lat: 51.5074, lng: -0.1278 } // fallback (London)
        });

        fetch("{{ route('shift.locations', ['shiftId' => $shift->id]) }}")
            .then(res => res.json())
            .then(locations => {
                if (locations.length > 0) {
                    const heatmapData = locations.map(loc => new google.maps.LatLng(loc.latitude, loc.longitude));

                    const heatmap = new google.maps.visualization.HeatmapLayer({
                        data: heatmapData,
                        map: map
                    });

                    // Auto zoom & center
                    const bounds = new google.maps.LatLngBounds();
                    heatmapData.forEach(point => bounds.extend(point));
                    map.fitBounds(bounds);

                    // Enforce minimum zoom level (e.g. 14)
                    const listener = google.maps.event.addListenerOnce(map, "bounds_changed", function () {
                        if (map.getZoom() > 16) { // adjust as needed
                            map.setZoom(16);
                        }
                    });
                } else {
                    alert("No location data found for this shift.");
                }
            });
    }

    window.onload = initMap;
</script>
</body>
</html>
