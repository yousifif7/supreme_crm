<table class="table table-bordered">
  <thead>
    <tr>
      <th>Guard</th>
      <th>Site</th>
      <th>Status</th>
      <th>Time</th>
    </tr>
  </thead>
  <tbody>
    @forelse($shifts as $shift)
      <tr>
        <td>{{ $shift->guard->name ?? '-' }}</td>
        <td>{{ $shift->site->name ?? '-' }}</td>
        <td>{{ $shift->status }}</td>
        <td>{{ $shift->start_time }} - {{ $shift->end_time }}</td>
      </tr>
    @empty
      <tr><td colspan="4">No shifts found</td></tr>
    @endforelse
  </tbody>
</table>
