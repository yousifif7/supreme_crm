<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Patrols Report - {{ $shiftDate->shift_date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 5px;
        }
        .info-section td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .patrol-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .patrol-section h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .patrol-details {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .patrol-details .row {
            display: table-row;
        }
        .patrol-details .col {
            display: table-cell;
            padding: 5px 10px;
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
            margin-top: 15px;
        }
        .media-section h4 {
            margin: 10px 0 5px 0;
            font-size: 14px;
        }
        .media-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .media-item {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: hidden;
        }
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .map-container {
            margin-top: 15px;
            text-align: center;
        }
        .map-container img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .scans-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        .scans-table th, .scans-table td {
            border: 1px solid #ddd;
            padding: 8px;
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
                <div class="media-grid">
                    @foreach($patrol->media->take(4) as $media)
                        @if(isset($media->base64Image) && $media->base64Image)
                        <div class="media-item">
                            <img src="{{ $media->base64Image }}" alt="Patrol Media">
                        </div>
                        @endif
                    @endforeach
                </div>
                @if($patrol->media->count() > 4)
                <p style="font-style: italic; color: #666; margin-top: 5px;">
                    + {{ $patrol->media->count() - 4 }} more files
                </p>
                @endif
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
            @endif
        </div>
        
        @if(!$loop->last && ($index + 1) % 2 == 0)
        <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer">
        <p>Generated on {{ \Carbon\Carbon::now()->format('d F Y H:i') }} | Page <span class="pagenum"></span></p>
    </div>
</body>
</html>
