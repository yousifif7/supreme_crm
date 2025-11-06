@extends('layouts.app')
@section('title', 'Client Dashboard')
@section('contents')
    <div class="page-wrapper">
        <div class="content">

            <h3>Welcome, {{ auth()->user()->name }}</h3>
            <div class="row">
                <div class="col-md-3">
                    <a href="{{ route('client.invoices.index') }}">
                    <div class="card p-3">
                        <h6>Total Invoices</h6>
                        <h3>{{ $invoicesCount ?? 0 }}</h3>
                    </div>
                </a>
                </div>
                <div class="col-md-3">
                    <div class="card p-3">
                        <h6>Outstanding</h6>
                        <h3>{{ number_format($outstanding ?? 0, 2) }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('client.sites.index') }}">
                    <div class="card p-3">
                        <h6>Sites</h6>
                        <h3>{{ $sitesCount ?? 0 }}</h3>
                    </div>
                </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('client.rota') }}">
                    <div class="card p-3">
                        <h6>Upcoming Shifts</h6>
                        <h3>{{ $upcomingShifts->count() ?? 0 }}</h3>
                    </div>
                </a>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <h3><b>My sites map</b></h3>
                            </div>
                        </div>
                        <div class="" style="padding-bottom:0px;">
                            <div id="map" style="height: 1000px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
            <div class="col-md-6">
                    <div class="card p-3">
                        <h6>Upcoming Shifts</h6>
                        <ul class="list-unstyled">
                            @forelse($upcomingShifts as $s)
                                <li>{{ $s->shift->site->site_name ?? 'N/A' }} — {{ format_date($s->shift_date) }}</li>
                            @empty
                                <li>No upcoming shifts</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('client.rota') }}">View rota</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        const userLocations = @json($userLocations ?? []);
        const siteLocations = @json($siteLocations ?? []);

        const iconByServiceId = {
            1: '/guard_icons/alarm_response.png',
            2: '/guard_icons/doghandlers.png',
            3: '/guard_icons/event_staff.png',
            4: '/guard_icons/key_holding.png',
            5: '/guard_icons/mobile_patrol.png',
            6: '/guard_icons/event_staff.png',
            7: '/guard_icons/fire_warden.png',
            8: '/guard_icons/close_protection.png',
        };

        const nameByServiceId = {
            1: 'Alarm Response',
            2: 'Doghandlers',
            3: 'Event Staff',
            4: 'Keyholding',
            5: 'Mobile Patrol',
            6: 'Static Guards',
            7: 'Fire Warden',
            8: 'Close Protection',
        };

        const siteIconHTML = '<i class="fas fa-building" style="color:#2c3e50;font-size:16px;"></i>';

        let map;
        let customMarkers = [];
        let currentInfoWindow = null;
        let geocoder;

        function initMap() {
            // --- Init map centered on England ---
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 10,
                center: {
                    lat: 51.50632911888075,
                    lng: -0.08967956053966389
                },
                gestureHandling: "auto", // user can drag/zoom
                mapTypeControl: true,
                streetViewControl: false,
            });

            geocoder = new google.maps.Geocoder();

            // --- Add user markers ---
            userLocations.forEach(loc => {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                const latLng = new google.maps.LatLng(lat, lng);
                const username = loc.name ?? 'Unknown';
                const serviceTypeId = loc.service_type_id ?? 6;
                const iconUrl = iconByServiceId[serviceTypeId] ?? null;
                const serviceName = nameByServiceId[serviceTypeId] ?? 'Service';

                addCustomMarker(latLng, iconUrl, username, serviceName, loc, false);
            });

            // --- Add site markers (geocode postal codes) ---
            siteLocations.forEach(loc => {
                if (!loc.postalcode) return;

                geocoder.geocode({
                    address: loc.postalcode
                }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const latLng = results[0].geometry.location;
                        addCustomMarker(latLng, siteIconHTML, loc.name, 'Site Location', loc, true);
                    }
                });
            });
        }

        function addCustomMarker(latLng, icon, displayName, serviceName, loc, isSite) {
            class CustomMarker extends google.maps.OverlayView {
                constructor(position) {
                    super();
                    this.position = position;
                    this.div = null;
                }

                onAdd() {
                    this.div = document.createElement("div");
                    this.div.className = "custom-marker";

                    if (isSite) {
                        this.div.innerHTML = `<div class="site-marker">${icon}</div>`;
                    } else {
                        const iconHTML = icon ?
                            `<img src="${icon}" alt="${serviceName}" style="width:24px;height:24px;border-radius:50%">` :
                            `<div style="width:20px;height:20px;background:red;border-radius:50%"></div>`;

                        this.div.innerHTML = `
                    <div class="pin">
                        <div class="circle">${iconHTML}</div>
                        <div class="triangle"></div>
                        <span class="username">${displayName}</span>
                    </div>
                `;
                    }

                    this.div.addEventListener("click", () => {
                        if (currentInfoWindow) currentInfoWindow.close();

                        const content = isSite ?
                            `
        <div style="
            max-width: 180px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 12px;
            line-height: 1.3;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fefefe;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            color: #34495e;
        ">
            <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px; color:#2c3e50;">
                <i class="fas fa-building" style="margin-right:4px;color:#2980b9;"></i>
                ${loc.name}
            </div>
            <div><strong>Postal Code:</strong> ${loc.postalcode}</div>
        </div>
    ` :
                            `
        <div style="
            max-width: 200px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 12px;
            line-height: 1.3;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fefefe;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            color: #34495e;
        ">
            <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px; color:#2c3e50;">
                ${displayName}
            </div>
            <div><strong>Service:</strong> ${serviceName}</div>
            <div><strong>Accuracy:</strong> ${loc.accuracy ?? 'N/A'} m</div>
            <div><strong>On Duty:</strong> ${loc.on_duty ? 'Yes' : 'No'}</div>
            <div><strong>Timestamp:</strong> ${loc.timestamp ?? ''}</div>
        </div>
    `;

                        currentInfoWindow = new google.maps.InfoWindow({
                            content: content,
                            position: latLng
                        });

                        currentInfoWindow.open(map);
                        currentInfoWindow.addListener("closeclick", () => currentInfoWindow = null);
                    });


                    const panes = this.getPanes();
                    panes.overlayMouseTarget.appendChild(this.div);
                }

                draw() {
                    const projection = this.getProjection();
                    const pos = projection.fromLatLngToDivPixel(this.position);
                    if (pos && this.div) {
                        this.div.style.left = pos.x + "px";
                        this.div.style.top = (pos.y - (isSite ? 10 : 20)) + "px";
                    }
                }

                onRemove() {
                    if (this.div && this.div.parentNode) this.div.parentNode.removeChild(this.div);
                    this.div = null;
                }
            }

            const marker = new CustomMarker(latLng);
            marker.setMap(map);
            customMarkers.push(marker);
        }
    </script>

    <style>
        .custom-marker {
            position: absolute;
            cursor: pointer;
            transform: translate(-50%, -100%);
            display: flex;
            align-items: flex-end;
        }

        .pin-wrapper {
            position: relative;
            display: flex;
            flex-direction: row;
            align-items: flex-end;
        }

        .pin {
            position: relative;
            width: 32px;
            height: 32px;
        }

        .circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

        .circle img {
            width: 24px;
            height: 24px;
        }

        .triangle {
            position: absolute;
            bottom: -8px;
            /* points below the circle */
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 8px solid red;
            /* same as circle */
            z-index: 1;
        }

        .username {
            margin-left: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #000;
            white-space: nowrap;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .site-marker {
            font-size: 16px;
            color: #2c3e50;
            cursor: pointer;
            transform: translate(-50%, -50%);
        }
    </style>

    <!-- Google Maps JS API (with Visualization library for heatmap) -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=visualization&callback=initMap"
        async defer></script>

@endsection
