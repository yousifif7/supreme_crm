<div class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="{{ url('dashboard') }}" class="logo logo-normal">
            <img src="{{ asset('assets/sp_logo.png') }}" alt="Logo">
        </a>
        <a href="{{ url('dashboard') }}" class="logo-small">
            <img src="{{ asset('assets/sp_logo.png') }}" alt="Logo">
        </a>
        <a href="{{ url('dashboard') }}" class="dark-logo">
            <img src="{{ asset('assets/sp_logo.png') }}" alt="Logo">
        </a>
    </div>
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li>
                    <ul>

                        <li class="{{ request()->is('dashboard*') ? 'active' : '' }}">
                            <a href="{{ url('dashboard') }}">
                                <i class="ti ti-layout-dashboard"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        @can('Read Security Board')
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ request()->is('scheduling') || request()->is('worker_calendar') || request()->is('site_calendar') || request()->is('today_rota') || request()->is('shifts') ? 'subdrop' : '' }}">
                                    <i class="ti ti-shield-half-filled"></i>
                                    <span>Roster</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="display: {{ request()->is('scheduling') || request()->is('worker_calendar') || request()->is('site_calendar') || request()->is('today_rota') || request()->is('shifts') ? 'block' : 'none' }};">
                                    <li class="{{ request()->is('scheduling') ? 'active' : '' }}">
                                        <a href="{{ url('scheduling') }}">
                                            <i class="ti ti-calendar-plus"></i>Scheduling </a>
                                    </li>
                                    <li class="{{ request()->is('worker_calendar') ? 'active' : '' }}">
                                        <a href="{{ url('worker_calendar') }}">
                                            <i class="ti ti-calendar-month"></i>Worker Calendar </a>
                                    </li>
                                    <li class="{{ request()->is('site_calendar') ? 'active' : '' }}">
                                        <a href="{{ url('site_calendar') }}">
                                            <i class="ti ti-calendar-time"></i>Site Calendar </a>
                                    </li>
                                    <li class="{{ request()->is('today_rota') ? 'active' : '' }}">
                                        <a href="{{ url('today_rota') }}">
                                            <i class="ti ti-calendar-time"></i>Today's Rota </a>
                                    </li>
                                    <li class="{{ request()->is('shifts') ? 'active' : '' }}">
                                        <a href="{{ url('shifts') }}">
                                            <i class="ti ti-calendar-time"></i>Manage Shift </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('Read User Management')
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ request()->is('users') || request()->is('roles') ? 'subdrop' : '' }}">
                                    <i class="ti ti-users"></i>
                                    <span>User Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="display: {{ request()->is('users') || request()->is('roles') ? 'block' : 'none' }};">
                                    <li class="{{ request()->is('users') ? 'active' : '' }}">
                                        <a href="{{ route('users.index') }}">
                                            <i class="ti ti-users"></i>All Users </a>
                                    </li>
                                    <li class="{{ request()->is('roles') ? 'active' : '' }}">
                                        <a href="{{ route('roles.index') }}">
                                            <i class="ti ti-accessible"></i>All Roles </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('Read Clients')
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ request()->is('clients') || request()->is('sites') ? 'subdrop' : '' }}">
                                    <i class="ti ti-heartbeat"></i>
                                    <span>Clients</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="display: {{ request()->is('clients') || request()->is('sites') ? 'block' : 'none' }};">
                                    <li class="{{ request()->is('clients') ? 'active' : '' }}">
                                        <a href="{{ url('clients') }}">
                                            <i class="ti ti-heartbeat"></i>Clients </a>
                                    </li>
                                    <li class="{{ request()->is('sites') ? 'active' : '' }}">
                                        <a href="{{ url('sites') }}">
                                            <i class="ti ti-world-pin"></i>Sites </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('Read Security Staff')
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ request()->is('employees') || request()->is('subcontractors') ? 'subdrop' : '' }}">
                                    <i class="ti ti-heartbeat"></i>
                                    <span>Security Staffs</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="display: {{ request()->is('employees') || request()->is('subcontractors') ? 'block' : 'none' }};">
                                    <li class="{{ request()->is('employees') ? 'active' : '' }}">
                                        <a href="{{ url('employees') }}">
                                            <i class="ti ti-users"></i>Staffs </a>
                                    </li>
                                    <li class="{{ request()->is('subcontractors') ? 'active' : '' }}">
                                        <a href="{{ url('subcontractors') }}">
                                            <i class="ti ti-users"></i>Subcontractors </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('Read Vehicle Management')
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ request()->is('vehicle_management') ? 'subdrop' : '' }}">
                                    <i class="ti ti-search"></i>
                                    <span>Vehicle Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: {{ request()->is('vehicle_management') ? 'block' : 'none' }};">
                                    <li class="{{ request()->is('vehicle_management') ? 'active' : '' }}">
                                        <a href="{{ route('vehicle.management') }}">
                                            <i class="ti ti-reorder"></i>Vehicle Managment </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ request()->is('documents*') || request()->is('invoices') ? 'subdrop' : '' }}">
                                <i class="fa fa-file"></i>
                                <span>Reports</span>
                                <span class="menu-arrow"></span>
                            </a>

                            <ul style="display: {{ request()->is('documents*') ? 'block' : 'none' }};">
                                <li class="{{ request()->is('documents*') ? 'active' : '' }}"><a href="{{ route('documents.report') }}"><i
                                            class="fa-solid fa-file-import"></i>Document Report</a></li>

                                <li class="{{ request()->is('incident_report*') ? 'active' : '' }}">
                                    <a href="{{ route('incident_report.index') }}">
                                        <i class="ti ti-checkup-list"></i>Incident report
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="{{ request()->is('invoices*') ? 'active' : '' }}">
                            <a href="{{ route('invoices.index') }}">
                                <i class="fa fa-file-invoice-dollar"></i>
                                <span>Invoices</span>
                            </a>
                        </li>

                        <li class="{{ request()->is('payrolls*') ? 'active' : '' }}">
                            <a href="{{ route('payrolls.index') }}">
                                <i class="ti ti-file-invoice"></i>
                                <span>Payrolls</span>
                            </a>
                        </li>


                        <li class="{{ request()->is('leaves*') ? 'active' : '' }}">
                            <a href="{{ route('leaves.index') }}">
                                <i class="ti ti-door-exit"></i>
                                <span>Holiday Mgt. </span>
                            </a>
                        </li>

                        <li class="{{ request()->is('restrictions*') ? 'active' : '' }}">
                            <a href="{{ route('restrictions.index') }}">
                                <i class="fa fa-house-lock"></i>
                                <span>Restrictions </span>
                            </a>
                        </li>

                        <li class="{{ request()->is('materials*') ? 'active' : '' }}">
                            <a href="{{ url('materials') }}">
                                <i class="fa fa-phone"></i>
                                <span>HR</span>
                            </a>
                        </li>

                        <li class="{{ request()->is('chat*') ? 'active' : '' }}">
                            <a href="{{ url('chat') }}">
                                <i class="bi bi-chat"></i>
                                <span>My Chats</span>
                            </a>
                        </li>
                        <li>
                            <!-- Logout Link -->
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ti ti-logout"></i>
                                <span>Logout</span>
                            </a>

                            <!-- Logout Form -->
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>

                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
