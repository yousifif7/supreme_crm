@extends('layouts.app')
@section('title', 'SPL Connect - Check Calls & Patrols Report')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        #ccp-report .nav-tabs .nav-link {
            font-weight: 600;
        }
        #ccp-report .badge-count {
            font-size: 11px;
        }
        /* Compact table: smaller font, tighter padding, controlled widths */
        #ccp-report table.ccp-table {
            font-size: 13px;
        }
        #ccp-report table.ccp-table th,
        #ccp-report table.ccp-table td {
            vertical-align: middle;
            padding: 6px 8px;
        }
        #ccp-report table.ccp-table th {
            white-space: nowrap;
        }
        #ccp-report .col-idx { width: 36px; }
        #ccp-report .col-time { width: 130px; white-space: nowrap; }
        #ccp-report .col-media { width: 96px; }
        #ccp-report .col-action { width: 150px; }
        #ccp-report .ccp-table .btn-xs {
            padding: 2px 7px;
            font-size: 11px;
            line-height: 1.4;
        }
        #ccp-report .ccp-table .media-link {
            display: block;
            margin-bottom: 3px;
        }
        #ccp-report .action-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
    </style>
@endsection

@section('contents')
    <div id="ccp-report" class="page-wrapper">
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Check Calls &amp; Patrols Report</h2>
                    <p class="text-muted mb-0">Completed items awaiting approval.</p>
                </div>
            </div>

            {{-- ---------------- Filters ---------------- --}}
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('report.checkcalls_patrols') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select name="client_id" id="client_id" class="form-select select2">
                                    <option value="">All Clients</option>
                                    @foreach ($clients as $id => $name)
                                        <option value="{{ $id }}" {{ (string) $selectedClient === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="site_id" class="form-label">Site</label>
                                <select name="site_id" id="site_id" class="form-select select2">
                                    <option value="">All Sites</option>
                                    @foreach ($sites as $id => $name)
                                        <option value="{{ $id }}" {{ (string) $selectedSite === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="staff_id" class="form-label">Staff</label>
                                <select name="staff_id" id="staff_id" class="form-select select2">
                                    <option value="">All Staff</option>
                                    @foreach ($staffs as $staff)
                                        <option value="{{ $staff->id }}" {{ (string) $selectedStaff === (string) $staff->id ? 'selected' : '' }}>
                                            {{ $staff->first_name }} {{ $staff->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="from_date" class="form-label">From Date</label>
                                <input type="date" name="from_date" id="from_date" class="form-control"
                                    value="{{ $fromDate ?? '' }}">
                            </div>

                            <div class="col-md-3">
                                <label for="to_date" class="form-label">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control"
                                    value="{{ $toDate ?? '' }}">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('report.checkcalls_patrols') }}" class="btn btn-secondary w-100">
                                    <i class="fa fa-rotate-left"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if (!$hasFilters)
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info mb-0">Please apply filters above to view check calls and patrols.</div>
                    </div>
                </div>
            @else
            {{-- ---------------- Tabs ---------------- --}}
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="ccpTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="checkcalls-tab" data-bs-toggle="tab"
                                data-bs-target="#checkcalls-pane" type="button" role="tab">
                                Check Calls
                                <span class="badge bg-secondary badge-count">{{ $checkcalls->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="patrols-tab" data-bs-toggle="tab"
                                data-bs-target="#patrols-pane" type="button" role="tab">
                                Patrols
                                <span class="badge bg-secondary badge-count">{{ $patrols->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- ---------------- Check Calls pane ---------------- --}}
                        <div class="tab-pane fade show active" id="checkcalls-pane" role="tabpanel">
                            @if ($checkcalls->isEmpty())
                                <div class="alert alert-warning mb-0">No completed check calls awaiting approval.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped ccp-table">
                                        <thead>
                                            <tr>
                                                <th class="col-idx">#</th>
                                                <th>Name</th>
                                                <th>Site</th>
                                                <th>Staff</th>
                                                <th class="col-time">Scheduled</th>
                                                <th class="col-media">Media</th>
                                                <th class="col-action">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($checkcalls as $key => $cc)
                                                @php
                                                    $sd = $cc->shiftDate;
                                                    $shift = $sd?->shift;
                                                    $staff = $cc->employee ?? $sd?->staff;
                                                @endphp
                                                <tr data-row-type="checkcall" data-id="{{ $cc->id }}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $cc->name ?? '-' }}</td>
                                                    <td>{{ $shift?->site?->site_name ?? 'N/A' }}</td>
                                                    <td>{{ $staff?->first_name ?? 'N/A' }} {{ $staff?->last_name ?? '' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($cc->scheduled_time)->format('d-m-Y H:i') }}</td>
                                                    <td>
                                                        @forelse ($cc->media as $media)
                                                            <a href="{{ asset($media->file_path) }}" target="_blank"
                                                                class="btn btn-xs btn-outline-primary media-link">View</a>
                                                        @empty
                                                            <span class="text-muted">—</span>
                                                        @endforelse
                                                    </td>
                                                    <td>
                                                        <div class="action-cell">
                                                            @if ($sd)
                                                                <a href="{{ route('shiftDates.view', $sd->id) }}"
                                                                    class="btn btn-xs btn-info" target="_blank" title="Open shift">
                                                                    <i class="fa fa-up-right-from-square"></i>
                                                                </a>
                                                            @endif
                                                            <button class="btn btn-xs btn-success approve-btn"
                                                                data-type="checkcall" data-id="{{ $cc->id }}">Approve</button>
                                                            <button class="btn btn-xs btn-danger reject-btn"
                                                                data-type="checkcall" data-id="{{ $cc->id }}">Reject</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- ---------------- Patrols pane ---------------- --}}
                        <div class="tab-pane fade" id="patrols-pane" role="tabpanel">
                            @if ($patrols->isEmpty())
                                <div class="alert alert-warning mb-0">No completed patrols awaiting approval.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped ccp-table">
                                        <thead>
                                            <tr>
                                                <th class="col-idx">#</th>
                                                <th>Name</th>
                                                <th>Site</th>
                                                <th>Staff</th>
                                                <th class="col-time">Start</th>
                                                <th class="col-media">Media</th>
                                                <th class="col-action">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($patrols as $key => $patrol)
                                                @php
                                                    $sd = $patrol->shift;
                                                    $shift = $sd?->shift;
                                                    $staff = $sd?->staff;
                                                @endphp
                                                <tr data-row-type="patrol" data-id="{{ $patrol->id }}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $patrol->name ?? '-' }}</td>
                                                    <td>{{ $shift?->site?->site_name ?? 'N/A' }}</td>
                                                    <td>{{ $staff?->first_name ?? 'N/A' }} {{ $staff?->last_name ?? '' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($patrol->start_time)->format('d-m-Y H:i') }}</td>
                                                    <td>
                                                        @forelse ($patrol->media as $media)
                                                            <a href="{{ asset($media->file_path) }}" target="_blank"
                                                                class="btn btn-xs btn-outline-primary media-link">View</a>
                                                        @empty
                                                            <span class="text-muted">—</span>
                                                        @endforelse
                                                    </td>
                                                    <td>
                                                        <div class="action-cell">
                                                            @if ($sd)
                                                                <a href="{{ route('shiftDates.view', $sd->id) }}"
                                                                    class="btn btn-xs btn-info" target="_blank" title="Open shift">
                                                                    <i class="fa fa-up-right-from-square"></i>
                                                                </a>
                                                            @endif
                                                            <button class="btn btn-xs btn-success approve-btn"
                                                                data-type="patrol" data-id="{{ $patrol->id }}">Approve</button>
                                                            <button class="btn btn-xs btn-danger reject-btn"
                                                                data-type="patrol" data-id="{{ $patrol->id }}">Reject</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            const csrf = $('meta[name="csrf-token"]').attr('content');

            // Both types use POST /{base}/{id}/{action}
            function endpoint(type, id, action) {
                const base = type === 'patrol' ? '/patrols' : '/checkcalls';
                return `${base}/${id}/${action}`;
            }

            function handleAction(btn, action) {
                const $btn = $(btn);
                const type = $btn.data('type');
                const id = $btn.data('id');
                const verb = action === 'approve' ? 'approve' : 'reject';
                const label = type === 'patrol' ? 'patrol' : 'check call';

                if (!confirm(`Are you sure you want to ${verb} this ${label}?`)) return;

                const $row = $btn.closest('tr');
                $row.find('.approve-btn, .reject-btn').prop('disabled', true);

                $.ajax({
                    url: endpoint(type, id, action),
                    type: 'POST',
                    data: { _token: csrf },
                    success: function () {
                        toastr.success(`${label.charAt(0).toUpperCase() + label.slice(1)} ${verb}d successfully`);
                        // Item is no longer pending -> drop it from the list, update the tab count.
                        const $pane = $row.closest('.tab-pane');
                        $row.remove();
                        const remaining = $pane.find('tbody tr').length;
                        const tabId = $pane.attr('id') === 'patrols-pane' ? '#patrols-tab' : '#checkcalls-tab';
                        $(tabId).find('.badge-count').text(remaining);
                        if (remaining === 0) {
                            $pane.find('.table-responsive').replaceWith(
                                `<div class="alert alert-warning mb-0">No completed ${type === 'patrol' ? 'patrols' : 'check calls'} awaiting approval.</div>`
                            );
                        }
                    },
                    error: function (xhr) {
                        let msg = `Error trying to ${verb}`;
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        toastr.error(msg);
                        $row.find('.approve-btn, .reject-btn').prop('disabled', false);
                    }
                });
            }

            $(document).on('click', '.approve-btn', function () { handleAction(this, 'approve'); });
            $(document).on('click', '.reject-btn', function () { handleAction(this, 'reject'); });
        });
    </script>
@endsection
