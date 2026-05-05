@if ($patrols->isNotEmpty())
    <div class="mb-3 d-flex justify-content-end gap-2">
        <a href="{{ route('patrols.export.pdf', $shiftDate->id) }}" class="btn btn-danger btn-sm" target="_blank">
            <i class="ti ti-file-type-pdf"></i> Export PDF
        </a>
    </div>

    <div style="overflow-x:auto;">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Start time</th>
                <th>Total Checkpoints</th>
                <th>Completed</th>
                <th>Issues</th>
                <th>Started at</th>
                <th>completed at</th>
                <th>Status</th>
                <th>Approval Status</th>
                <th>Scans</th>
                <th>Media</th>
                <th>Action</th>
                <th>Map</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($patrols as $patrol)
                @php
                    $patrolMedia = $patrol->media ?? collect();
                    $patrolScans = $patrol->scans ?? collect();
                @endphp
                <tr>
                    <td>{{ $patrol->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($patrol->start_time)->format('d-m-Y H:i') }}</td>
                    <td>{{ $patrol->total_checkpoints }}</td>
                    <td>{{ $patrol->completed_checkpoints }}</td>
                    <td>{{ $patrol->issues_reported }}</td>
                    @if ($patrol->started_at)
                        <td>{{ \Carbon\Carbon::parse($patrol->started_at ?? '')->format('H:i') }}</td>
                    @else
                        <td></td>
                    @endif
                    @if ($patrol->completed_at)
                        <td>{{ \Carbon\Carbon::parse($patrol->completed_at ?? '')->format('H:i') }}</td>
                    @else
                        <td></td>
                    @endif
                    <td>
                        @if ($patrol->status == 'pending')
                            <p class="bg-warning text-center">Pending</p>
                        @elseif ($patrol->status == 'in_progress')
                            <p class="bg-primary text-center">In Progress</p>
                        @elseif($patrol->status == 'completed')
                            <p class="bg-success text-center">Completed</p>
                        @elseif($patrol->status == 'missed')
                            <p class="bg-danger text-center">Missed</p>
                        @endif
                    </td>
                    <td>
                        @if($patrol->approval_status == 'pending' || $patrol->approval_status == null)
                            <p class="bg-warning">Pending</p>
                        @elseif($patrol->approval_status == 'approved')
                            <p class="bg-success">Approved</p>
                        @elseif($patrol->approval_status == 'rejected')
                            <p class="bg-danger">Rejected</p>
                        @endif

                        @if($patrol->status == 'completed' && ($patrol->approval_status == 'pending' || $patrol->approval_status == null ))
                            <button class="btn btn-sm btn-success approve-patrol-btn" data-id="{{ $patrol->id }}">Approve</button>
                            <button class="btn btn-sm btn-danger reject-patrol-btn" data-id="{{ $patrol->id }}">Reject</button>
                        @endif
                    </td>
                    <td>
                        @if($patrolScans->isNotEmpty())
                            @foreach($patrolScans as $scan)
                                <div class="mb-1">
                                    <small class="text-muted">{{ strtoupper($scan->scan_method) }} — {{ optional(\Carbon\Carbon::parse($scan->timestamp))->format('d-m H:i') }}</small>
                                    <div>
                                        @php
                                            $scanPayload = [
                                                'id' => $scan->id,
                                                'scan_method' => $scan->scan_method,
                                                'timestamp' => $scan->timestamp,
                                                'notes' => $scan->notes ?? null,
                                                'issues_found' => $scan->issues_found ?? null,
                                                'latitude' => $scan->latitude ?? null,
                                                'longitude' => $scan->longitude ?? null,
                                                'media' => ($scan->media ?? collect())->pluck('file_path')->toArray(),
                                            ];
                                        @endphp
                                    </div>
                                </div>
                            @endforeach
                        @else
                            No scans
                        @endif
                    </td>
                    <td>
                        @forelse ($patrolMedia as $media)
                            <a href="{{ asset($media->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View File</a><br>
                        @empty
                            No media
                        @endforelse
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-patrol-btn"
                            data-id="{{ $patrol->id }}"
                            data-name="{{ $patrol->name }}"
                            data-time="{{ \Carbon\Carbon::parse($patrol->start_time)->format('H:i') }}"
                            data-status="{{ $patrol->status }}"
                            data-approval-status="{{ $patrol->approval_status ?? 'pending' }}">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-danger delete-patrol-btn" data-id="{{ $patrol->id }}">Delete</button>
                    </td>
                    <td style="min-width:350px">
                        <div id="patrol-map-{{ $patrol->id }}" style="height:250px; width:100%; border-radius:8px;"></div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
@else
    <div class="alert alert-info" role="alert">
        No patrols available for this shift.
    </div>
@endif
