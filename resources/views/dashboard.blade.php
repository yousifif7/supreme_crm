@extends('layouts.app')
@section('title', 'SP CRM')
@section('contents')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">

            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Dashboard</h2>

                </div>
            </div>
            <!-- /Breadcrumb -->

            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-primary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Total Security Staff</p>
                                    <h5>{{ $staffs->count() }}</h5>
                                </div>
                            </div>
                            {{-- <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ $clientgrowthPercentage }}%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-danger fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>{{ $clientgrowthPercentage }}% </span>
                                from last week</p> --}}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-secondary flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-heartbeat text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Total Clients</p>
                                    <h5>{{ $clients->count() }}</h5>
                                </div>
                            </div>
                            {{-- <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-secondary" role="progressbar"
                                    style="width: {{ $clientgrowthPercentage }}%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>{{ $clientgrowthPercentage }}% </span>
                                from last week</p> --}}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-danger flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-stairs-up text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">No of Invoice Sent</p>
                                    <h5>{{ $invoices->count() }}</h5>
                                </div>
                            </div>
                            {{-- <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-pink" role="progressbar"
                                    style="width: {{ $invoicerowthPercentage }}%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>+{{ $invoicerowthPercentage }}% </span>
                                from last week</p> --}}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-md br-10 icon-rotate bg-purple flex-shrink-0">
                                    <span class="d-flex align-items-center"><i
                                            class="ti ti-users-group text-white fs-16"></i></span>
                                </div>
                                <div class="ms-3">
                                    <p class="fw-medium text-truncate mb-1">Pending Review</p>
                                    <h5>{{ $review }}</h5>
                                </div>
                            </div>
                            {{-- <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-purple" role="progressbar"
                                    style="width: {{ $reviewrowthPercentage }}%"></div>
                            </div>
                            <p class="fw-medium fs-13 mb-0"><span class="text-success fs-12"><i
                                        class="ti ti-arrow-wave-right-up me-1"></i>+{{ $reviewrowthPercentage }}% </span>
                                from last week</p> --}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Live Tracking</div>
                        </div>
                        <div class="card-body">
                            <div id="map" style="height: 500px;"></div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                            <h5 class="mb-2">Today Shifts (Live)</h5>

                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0">
                                    <thead>
                                        <tr class="text-center">
                                            <th>TIME</th>
                                            <th>PERSON</th>
                                            <th>IN</th>
                                            <th>BREAK</th>
                                            <th>OUT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($shifts as $shift)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}
                                                </td>
                                                <td>{{ $shift->shift?->staff?->fore_name }}
                                                    {{ $shift->shift?->staff?->sur_name }}</td>
                                                <td>X</td>
                                                <td>{{ $shift->break_time }}
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                                                </td>
                                            </tr>
                                        @endforeach



                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
                <!--
                                            <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                                                <div class="card flex-fill">
                                                    <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                                                        <h5 class="mb-2">Action Required</h5>

                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-nowrap mb-0 action_require-table">

                                                                <tbody>
                                                                    <tr class="required">
                                                                        <td>0</td>
                                                                        <td>Need Approval</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>22</td>
                                                                        <td>Check points</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>6</td>
                                                                        <td>Awaiting acceptance</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>20</td>
                                                                        <td>Awaiting Start</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>1</td>
                                                                        <td>Pending Dispatch</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>12</td>
                                                                        <td>Shift Started</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr class="required">
                                                                        <td>0</td>
                                                                        <td>Shift Ended</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr class="required">
                                                                        <td>0</td>
                                                                        <td>Shift Rejected</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>4</td>
                                                                        <td>Visa Expiry</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>1000</td>
                                                                        <td>S.I.A Expiry</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr class="required">
                                                                        <td>4</td>
                                                                        <td>S.I.A last checked today</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>51</td>
                                                                        <td>S.I.A not found</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>2</td>
                                                                        <td>S.I.A revoked</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>9</td>
                                                                        <td>Passport expiry</td>
                                                                        <td><a href="Clients-sites.html" class="btn btn-light btn-icon btn-sm"><i
                                                                                    class="ti ti-arrow-right fs-16"></i></a></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            -->
            </div>
            <div class="row">
                <div class="col-xl-6 col-lg-6 col-xxl-6 col-12 d-flex">
                    <div class="card flex-fill">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                <h5>Upcomming Shifts</h5>

                            </div>
                        </div>
                        <div class="card-body pb-0">

                            <div id="revenue-income1"></div>
                        </div>
                    </div>
                </div>
                <!--
                                            <div class="col-xxl-6 col-12 col-xl-6 d-flex">
                                                <div class="card flex-fill">
                                                    <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                                                        <h5 class="mb-2">Latest Actions</h5>

                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-nowrap mb-0 lastest_action-table">

                                                                <tbody>
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <p>43 minutes ago</p>
                                                                                <span class="text-info">MUHAMMAD NASIR (SPL)</span>
                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <p><b>Job Accepted</b></p>
                                                                            <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <p>3 hours ago</p>
                                                                                <span class="text-info">HEENARANA</span>
                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                                            <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <p>3 hours ago</p>
                                                                                <span class="text-info">HEENARANA</span>
                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                                            <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <p>3 hours ago</p>
                                                                                <span class="text-info">HEENARANA</span>
                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                                            <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <div>
                                                                                <p>3 hours ago</p>
                                                                                <span class="text-info">HEENARANA</span>
                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <p><b>Job Created Successfully - Via Job Duplicate</b></p>
                                                                            <span>{Start time:09/05/2025 00:00 End time:09/05/2025 00:00}</span>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            -->
            </div>

        </div>



    </div>
    <!-- /Page Wrapper -->
@endsection

@section('scripts')
    <script>
        $.ajax({
            url: `${baseUrl}/shifts/stats`,
            method: 'GET',
            success: function(res) {
                var sColStacked = {
                    chart: {
                        height: 230,
                        type: 'bar',
                        stacked: true,
                        toolbar: {
                            show: false
                        }
                    },
                    colors: ['#FF6F28', '#F8F9FA'],
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            legend: {
                                position: 'bottom',
                                offsetX: -10,
                                offsetY: 0
                            }
                        }
                    }],
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            borderRadiusWhenStacked: 'all',
                            horizontal: false,
                            endingShape: 'rounded'
                        },
                    },
                    series: [{
                        name: 'Income',
                        data: res.shift
                    }, {
                        name: 'Shift',
                        data: res.shift
                    }],
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
                            'Oct', 'Nov', 'Dec'
                        ],
                        labels: {
                            style: {
                                colors: '#6B7280',
                                fontSize: '13px',
                            }
                        }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        labels: {
                            offsetX: -15,
                            style: {
                                colors: '#6B7280',
                                fontSize: '13px',
                            },
                            formatter: function(value) {
                                return value + "";
                            }
                        }
                    },
                    grid: {
                        borderColor: 'transparent',
                        strokeDashArray: 5,
                        padding: {
                            left: -8
                        },
                    },
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ""
                            }
                        }
                    },
                    fill: {
                        opacity: 1
                    }
                };

                var chart = new ApexCharts(document.querySelector("#revenue-income1"), sColStacked);
                chart.render();
            }
        });
    </script>
@endsection
