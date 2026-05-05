@if (collect($shiftDate->logs)->isNotEmpty())
    <div style="overflow-x:auto;">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>User</th>
                <th>Description</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shiftDate->logs as $log)
                <tr>
                    <td>{{ $log->user_name ?? 'N/A' }}</td>
                    <td>{!! $log->description !!}</td>
                    <td>{{ $log->created_at->format('m-d-Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
@else
    <div class="alert alert-info" role="alert">
        No logs available for this shift.
    </div>
@endif
