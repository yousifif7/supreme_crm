<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Patrols Report - {{ $shiftDate->shift_date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 10px;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 5px;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 3px;
        }
        .info-section td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .patrol-section {
            margin-bottom: 10px;
            page-break-inside: auto;
            break-inside: auto;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }
        .patrol-section h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 3px;
            font-size: 14px;
        }
        .patrol-details {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .patrol-details .row {
            display: table-row;
        }
        .patrol-details .col {
            display: table-cell;
            padding: 3px 6px;
            border-bottom: 1px solid #eee;
        }
        .patrol-details .label {
            font-weight: bold;
            width: 30%;
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-in_progress { background: #007bff; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .status-missed { background: #dc3545; color: #fff; }
        .media-section {
            margin-top: 8px;
        }
        .media-section h4 {
            margin: 6px 0 4px 0;
            font-size: 12px;
        }
        .media-grid {
            width: 100%;
            margin-top: 6px;
        }
        .media-row {
            width: 100%;
            margin-bottom: 6px;
            white-space: nowrap;
        }
        .media-item {
            display: inline-block;
            vertical-align: top;
            width: 19%;
            height: 82px;
            margin-right: 1%;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: hidden;
            box-sizing: border-box;
        }
        .media-row .media-item:last-child {
            margin-right: 0;
        }
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .map-container {
            margin-top: 8px;
            text-align: center;
        }
        .map-container img {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .scans-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 11px;
        }
        .scans-table th, .scans-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        .scans-table th {
            background: #3498db;
            color: white;
        }
        .scans-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Patrols Report</h1>
        <p><strong>{{ $site->site_name }}</strong></p>
        <p>{{ \Carbon\Carbon::parse($shiftDate->shift_date)->format('l, d F Y') }}</p>
        <p>Guard: {{ $shiftDate->staff ? $shiftDate->staff->first_name . ' ' . $shiftDate->staff->last_name : 'Unassigned' }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>Site Address:</td>
                <td>{{ $site->address }}</td>
            </tr>
            <tr>
                <td>Shift Time:</td>
                <td>{{ \Carbon\Carbon::parse($shiftDate->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shiftDate->end_time)->format('H:i') }}</td>
            </tr>
            <tr>
                <td>Total Patrols:</td>
                <td>{{ $patrols->count() }}</td>
            </tr>
            <tr>
                <td>Completed Patrols:</td>
                <td>{{ $patrols->where('status', 'completed')->count() }}</td>
            </tr>
            <tr>
                <td>Total Checkpoints:</td>
                <td>{{ $checkpoints->count() }}</td>
            </tr>
        </table>
    </div>

    @foreach($patrols as $index => $patrol)
        <div class="patrol-section">
            <h3>{{ $patrol->name }}</h3>
            
            <div class="patrol-details">
                <div class="row">
                    <div class="col label">Scheduled Time:</div>
                    <div class="col">{{ \Carbon\Carbon::parse($patrol->start_time)->format('d-m-Y H:i') }}</div>
                </div>
                <div class="row">
                    <div class="col label">Status:</div>
                    <div class="col">
                        <span class="status-badge status-{{ $patrol->status }}">
                            {{ ucfirst(str_replace('_', ' ', $patrol->status)) }}
                        </span>
                    </div>
                </div>
                @if($patrol->started_at)
                <div class="row">
                    <div class="col label">Started At:</div>
                    <div class="col">{{ \Carbon\Carbon::parse($patrol->started_at)->format('d-m-Y H:i:s') }}</div>
                </div>
                @endif
                @if($patrol->firstLocation)
                <div class="row">
                    <div class="col label">First Location:</div>
                    <div class="col">
                        {{ $patrol->firstLocation['latitude'] }}, {{ $patrol->firstLocation['longitude'] }}
                        @if($patrol->firstLocation['address'])
                            <br><small style="color: #666;">{{ $patrol->firstLocation['address'] }}</small>
                        @endif
                        <br><small style="color: #999;">{{ \Carbon\Carbon::parse($patrol->firstLocation['timestamp'])->format('H:i:s') }}</small>
                    </div>
                </div>
                @endif
                @if($patrol->lastLocation)
                <div class="row">
                    <div class="col label">Last Location:</div>
                    <div class="col">
                        {{ $patrol->lastLocation['latitude'] }}, {{ $patrol->lastLocation['longitude'] }}
                        @if($patrol->lastLocation['address'])
                            <br><small style="color: #666;">{{ $patrol->lastLocation['address'] }}</small>
                        @endif
                        <br><small style="color: #999;">{{ \Carbon\Carbon::parse($patrol->lastLocation['timestamp'])->format('H:i:s') }}</small>
                    </div>
                </div>
                @endif
                @if($patrol->completed_at)
                <div class="row">
                    <div class="col label">Completed At:</div>
                    <div class="col">{{ \Carbon\Carbon::parse($patrol->completed_at)->format('d-m-Y H:i') }}</div>
                </div>
                <div class="row">
                    <div class="col label">Duration:</div>
                    <div class="col">
                        {{ \Carbon\Carbon::parse($patrol->started_at)->diffForHumans(\Carbon\Carbon::parse($patrol->completed_at), true) }}
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col label">Checkpoints:</div>
                    <div class="col">{{ $patrol->completed_checkpoints }} / {{ $patrol->total_checkpoints }}</div>
                </div>
                @if($patrol->issues_reported)
                <div class="row">
                    <div class="col label">Issues Reported:</div>
                    <div class="col" style="color: #dc3545; font-weight: bold;">{{ $patrol->issues_reported }}</div>
                </div>
                @endif
            </div>

            @if($patrol->scans->isNotEmpty())
            <div class="media-section">
                <h4>Checkpoint Scans ({{ $patrol->scans->count() }})</h4>
                <table class="scans-table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Timestamp</th>
                            <th>Notes</th>
                            <th>Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patrol->scans as $scan)
                        <tr>
                            <td>{{ strtoupper($scan->scan_method) }}</td>
                            <td>{{ \Carbon\Carbon::parse($scan->timestamp)->format('H:i:s') }}</td>
                            <td>{{ $scan->notes ?? '-' }}</td>
                            <td>{{ $scan->issues_found ? 'Yes' : 'No' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($patrol->media->isNotEmpty())
            <div class="media-section">
                <h4>Patrol Media ({{ $patrol->media->count() }} files)</h4>
                @php
                    $mediaImages = $patrol->media->filter(function ($media) {
                        return isset($media->base64Image) && $media->base64Image;
                    })->values();
                @endphp
                <div class="media-grid">
                    @foreach($mediaImages->chunk(5) as $row)
                        <div class="media-row">
                            @foreach($row as $media)
                                <div class="media-item">
                                    <img src="{{ $media->base64Image }}" alt="Patrol Media">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($patrol->locations && $patrol->locations->isNotEmpty())
            <div class="map-container">
                <h4>Patrol Route Map</h4>
                @if(isset($patrol->mapImage) && $patrol->mapImage)
                    <img src="{{ $patrol->mapImage }}" alt="Patrol Route Map" style="max-width: 100%; height: auto;">
                @else
                    <div style="padding: 20px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                        <p style="margin: 0; color: #666;">
                            Route: {{ $patrol->locations->count() }} location points recorded
                        </p>
                    </div>
                @endif
            </div>

            {{-- Exact last-location (zoomed) image removed to save space --}}
            @endif
        </div>
        
        {{-- Removed forced page-breaks so patrols flow continuously across pages --}}
    @endforeach

    <div class="footer">
        <p>Generated on {{ \Carbon\Carbon::now()->format('d F Y H:i') }} | Page <span class="pagenum"></span></p>
    </div>
</body>
</html>
