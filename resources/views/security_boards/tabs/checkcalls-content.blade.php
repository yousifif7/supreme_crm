@if ($checkcalls->isNotEmpty())
    @php
        $checkCallEmployeeIds = $checkcalls->pluck('employee_id')->filter()->unique()->values();
        $checkCallUsersById = $checkCallEmployeeIds->isNotEmpty()
            ? \App\Models\User::whereIn('id', $checkCallEmployeeIds)->get()->keyBy('id')
            : collect();
        $checkCallMediaByCallId = \App\Models\CheckCallMedia::whereIn('check_call_id', $checkcalls->pluck('id'))
            ->get()
            ->groupBy('check_call_id');
    @endphp

    <div style="overflow-x:auto;">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Staff</th>
                <th>Time</th>
                <th>Status</th>
                <th>Approval Status</th>
                <th>Media</th>
                <th>Action</th>
                <th>Guard Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($checkcalls as $checkcall)
                @php
                    $employee = $checkCallUsersById->get($checkcall->employee_id);
                    $checkCallMedia = $checkCallMediaByCallId->get($checkcall->id, collect());
                @endphp
                <tr>
                    <td>{{ $checkcall?->name }}</td>
                    <td>{{ $employee?->first_name }} {{ $employee?->last_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($checkcall->scheduled_time)->format('d-m-Y H:i') }}</td>
                    <td>
                        @if ($checkcall->status == 'pending')
                            <p class="bg-warning text-center">Pending</p>
                        @elseif ($checkcall->status == 'missed')
                            <p class="bg-danger text-center">Missed</p>
                        @elseif($checkcall->status == 'completed')
                            <p class="bg-success text-center">Completed</p>
                        @endif
                    </td>
                    <td>
                        @if ($checkcall->approval_status == 'pending' || $checkcall->approval_status == null)
                            <p class="bg-warning">Pending</p>
                        @elseif ($checkcall->approval_status == 'approved')
                            <a class="bg-success">Approved</a>
                        @elseif($checkcall->approval_status == 'rejected')
                            <a class="bg-danger">Rejected</a>
                        @endif

                        @if($checkcall->status == 'completed' && ($checkcall->approval_status == 'pending' || $checkcall->approval_status == null) )
                            <div class="mt-1">
                                <button class="btn btn-sm btn-success approve-checkcall-btn"
                                    data-id="{{ $checkcall->id }}"
                                    data-name="{{ $checkcall->name }}"
                                    data-time="{{ \Carbon\Carbon::parse($checkcall->scheduled_time)->format('d-m-Y H:i') }}">
                                    Approve
                                </button>
                                <button class="btn btn-sm btn-danger reject-checkcall-btn"
                                    data-id="{{ $checkcall->id }}"
                                    data-name="{{ $checkcall->name }}"
                                    data-time="{{ \Carbon\Carbon::parse($checkcall->scheduled_time)->format('d-m-Y H:i') }}">
                                    Reject
                                </button>
                            </div>
                        @endif
                    </td>
                    <td>
                        @forelse ($checkCallMedia as $media)
                            <a href="{{ asset($media->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View File</a><br>
                        @empty
                            No media
                        @endforelse
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-checkcall-btn"
                            data-id="{{ $checkcall->id }}"
                            data-name="{{ $checkcall->name }}"
                            data-time="{{ $checkcall->scheduled_time }}"
                            data-status="{{ $checkcall->status }}"
                            data-approval_status="{{ $checkcall->approval_status ?? 'pending' }}">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-danger delete-checkcall-btn" data-id="{{ $checkcall->id }}">Delete</button>
                    </td>
                    <td>{{ $checkcall->notes ?? 'ــ' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
@else
    <div class="alert alert-info" role="alert">
        No check calls available for this shift.
    </div>
@endif
