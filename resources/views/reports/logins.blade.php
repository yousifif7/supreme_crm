@extends('layouts.app')
@section('title', 'Reports - Login Activity')

@section('contents')
    <div class="page-wrapper">
        <div class="content">
            <h2 class="mb-3">Login Activity Report</h2>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.logins') }}" class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ $startDate ?? request()->input('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control"
                                value="{{ $endDate ?? request()->input('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <select name="search" class="form-select select2_users" id="userSelect">
                                <option value="">--choose--</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                        @php
                                            $roleName = $user->getRoleNames()->first() ?? '';
                                            $roleDisplay = $roleName ? ucwords(str_replace('_', ' ', $roleName)) : '';
                                        @endphp
                                        @if($roleDisplay)
                                            &nbsp;<small class="text-muted">({{ $roleDisplay }})</small>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ route('reports.logins', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                            class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('reports.logins', array_merge(request()->query(), ['export' => 'excel'])) }}"
                            class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if (!($hasFilters ?? false))
                        <div class="alert alert-info m-3">Please apply at least one filter (start date, end date or search)
                            to view login activity.</div>
                    @else
                        @if ($activities->isEmpty())
                            <div class="alert alert-warning m-3">No login activity found for the current filters.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table datatables table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Login At</th>
                                            <th>Logout At</th>
                                            <th>Duration</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($activities as $key => $a)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ optional($a->user)->first_name }} {{ optional($a->user)->last_name }}
                                                </td>
                                                <td>{{ optional($a->user)->email }}</td>
                                                <td>{{ optional($a->login_at)->format('Y-m-d H:i:s') ?? '' }}</td>
                                                <td>{{ optional($a->logout_at)->format('Y-m-d H:i:s') ?? '' }}</td>
                                                <td>
                                                    @if ($a->login_at && $a->logout_at)
                                                        @php
                                                            $totalMins = (int) round(abs($a->login_at->diffInMinutes($a->logout_at)));
                                                            $days = intdiv($totalMins, 1440);
                                                            $hours = intdiv($totalMins % 1440, 60);
                                                            $mins = $totalMins % 60;
                                                            $parts = [];
                                                            if ($days > 0) $parts[] = $days . ' ' . ($days === 1 ? 'Day' : 'Days');
                                                            if ($hours > 0) $parts[] = $hours . ' ' . ($hours === 1 ? 'Hour' : 'Hours');
                                                            if ($mins > 0) $parts[] = $mins . ' ' . ($mins === 1 ? 'Min' : 'Mins');
                                                        @endphp
                                                        {{ $parts ? implode(' ', $parts) : '0 Mins' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $a->ip_address ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatables').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [3, 'desc']
                ],
            });

            $('.select2_users').select2({
                placeholder: "--choose--",
                allowClear: true,
                width: '100%',
            });
        });
    </script>
@endsection
