  <div class="sidebar" id="sidebar">
      <!-- Logo -->
      <div class="sidebar-logo">
          <a href="{{ url('dashboard') }}" class="logo logo-normal">
              <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="Logo">
          </a>
          <a href="{{ url('dashboard') }}" class="logo-small">
              <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="Logo">
          </a>
          <a href="{{ url('dashboard') }}" class="dark-logo">
              <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="Logo">
          </a>
      </div>
      <div class="sidebar-inner slimscroll">
          <div id="sidebar-menu" class="sidebar-menu">
              <ul>
                  <li>
                      <ul>

                        <li class="{{ request()->is('dashboard*') || request()->is('client/dashboard*') ? 'active' : '' }}">
                            <a href="{{ auth()->user()->hasRole('client') ? route('client.dashboard') : route('dashboard') }}">
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
                              <li class="{{ request()->is('vehicle_management*') ? 'active' : '' }}">
                                  <a href="{{ route('vehicle.management') }}">
                                      <i class="ti ti-reorder"></i>
                                      <span>Vehicle Management </span>
                                  </a>
                              </li>
                          @endcan

                          @can('Read Reports Managment')
                              <li class="submenu">
                                  <a href="javascript:void(0);"
                                      class="{{ request()->is('documents*') || request()->is('invoices') ? 'subdrop' : '' }}">
                                      <i class="fa fa-file"></i>
                                      <span>Reports</span>
                                      <span class="menu-arrow"></span>
                                  </a>

                                  <ul style="display: {{ request()->is('documents*') ? 'block' : 'none' }};">
                                      <li class="{{ request()->is('documents*') ? 'active' : '' }}"><a
                                              href="{{ route('documents.report') }}"><i
                                                  class="fa-solid fa-file-import"></i>Document Report</a>
                                      </li>

                                      <li class="{{ request()->is('incident_report*') ? 'active' : '' }}">
                                          <a href="{{ route('incident_report.index') }}">
                                              <i class="ti ti-alert-circle"></i> Incident Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('dobs*') ? 'active' : '' }}">
                                          <a href="{{ route('dobs.index') }}">
                                              <i class="ti ti-calendar"></i> DOB Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('/reports/employment*') ? 'active' : '' }}">
                                          <a href="{{ route('reports.employment') }}">
                                              <i class="ti ti-users"></i> Employment Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('staff-report*') ? 'active' : '' }}">
                                          <a href="{{ route('staff.report') }}">
                                              <i class="ti ti-users"></i> Staff Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('reports/shifts*') ? 'active' : '' }}">
                                          <a href="{{ route('reports.shift') }}">
                                              <i class="ti ti-clock"></i> Shift Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('booking/report*') ? 'active' : '' }}">
                                          <a href="{{ route('booking.report') }}">
                                              <i class="ti ti-check"></i> Book On Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('reports/clients*') ? 'active' : '' }}">
                                          <a href="{{ route('reports.clients') }}">
                                              <i class="ti ti-building"></i> Client Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('reports/checkpoints*') ? 'active' : '' }}">
                                          <a href="{{ route('report.checkpoints') }}">
                                              <i class="ti ti-map-pin"></i> Check Point Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('reports/performance*') ? 'active' : '' }}">
                                          <a href="{{ route('performance.report') }}">
                                              <i class="ti ti-chart-bar"></i> Performance Report
                                          </a>
                                      </li>

                                      <li class="{{ request()->is('reports/salary*') ? 'active' : '' }}">
                                          <a href="{{ route('salary.report') }}">
                                              <i class="ti ti-cash"></i> Salary Report
                                          </a>
                                      </li>

                                      {{-- <li class="{{ request()->is('reports/employment*') ? 'active' : '' }}">
                                          <a href="{{ route('reports.employment') }}">
                                              <i class="ti ti-checkup-list"></i>Salary Sheet
                                          </a>
                                      </li> --}}
                                  </ul>
                              </li>
                          @endcan

                          @can('Read Invoice Management')
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
                          @endcan

                          @can('Read HR Managment')
<li class="submenu">
    <a href="javascript:void(0);"
        class="{{ request()->is('leaves*') || request()->is('calendar') || request()->is('hr') || request()->is('leaves/pending') || request()->is('admin/setting') || request()->is('page/form') || request()->is('digital/form/index') || request()->is('form/shows') || request()->is('application/form/show') ? 'subdrop' : '' }}">
        <i class="fa fa-phone"></i>
        <span>HR Management</span>
        <span class="menu-arrow"></span>
    </a>

    <ul style="display: {{ request()->is('leaves*') || request()->is('calendar') || request()->is('hr') || request()->is('leaves/pending') || request()->is('admin/setting') || request()->is('page/form') || request()->is('digital/form/index') || request()->is('form/shows') || request()->is('application/form/show') ? 'block' : 'none' }};">
        <li class="{{ request()->is('hr') ? 'active' : '' }}">
            <a href="{{ route('materials.index') }}"><i class="fa fa-phone"></i> HR</a>
        </li>
        <li class="{{ request()->is('leaves') ? 'active' : '' }}">
            <a href="{{ route('leaves.index') }}"><i class="ti ti-door-exit"></i> Leaves</a>
        </li>
        <li class="{{ request()->is('leaves/pending') ? 'active' : '' }}">
            <a href="{{ route('leaves.pending') }}"><i class="ti ti-door-exit"></i> New Leave Requests</a>
        </li>
        <li class="{{ request()->is('calendar') ? 'active' : '' }}">
            <a href="{{ url('calendar') }}"><i class="ti ti-calendar"></i> Holidays Calendar</a>
        </li>
        <li class="{{ request()->is('admin/setting') ? 'active' : '' }}">
            <a href="{{ url('admin/setting') }}"><i class="ti ti-settings"></i> General Setting</a>
        </li>
        <li class="{{ request()->is('page/form') ? 'active' : '' }}">
            <a href="{{ url('page/form') }}"><i class="ti ti-file-text"></i> Pages</a>
        </li>
        <li class="{{ request()->is('digital/form/index') ? 'active' : '' }}">
            <a href="{{ url('digital/form/index') }}"><i class="ti ti-layout"></i> Digital Form</a>
        </li>
        <li class="{{ request()->is('form/shows') ? 'active' : '' }}">
            <a href="{{ url('form/shows') }}"><i class="ti ti-list"></i> Form List</a>
        </li>
        <li class="{{ request()->is('application/form/show') ? 'active' : '' }}">
            <a href="{{ url('application/form/show') }}"><i class="ti ti-clipboard"></i> Application Form</a>
        </li>
         <li class="{{ request()->is('User/form/incident/data') ? 'active' : '' }}">
            <a href="{{ url('User/form/incident/data') }}"><i class="ti ti-clipboard"></i> Incident Data</a>
        </li>

        
    </ul>
</li>
@endcan


                          @can('Read Restrictions')
                              <li class="{{ request()->is('restrictions*') ? 'active' : '' }}">
                                  <a href="{{ route('restrictions.index') }}">
                                      <i class="fa fa-house-lock"></i>
                                      <span>Restrictions </span>
                                  </a>
                              </li>
                          @endcan

                          {{-- @can('Read HR Managment')
                              <li class="{{ request()->is('hr*') ? 'active' : '' }}">
                                  <a href="{{ url('hr') }}">
                                      <i class="fa fa-phone"></i>
                                      <span>HR</span>
                                  </a>
                              </li>
                          @endcan --}}

                          @can('Read Chat')
                              <li class="{{ request()->is('chat*') ? 'active' : '' }}">
                                  <a href="{{ url('chat') }}">
                                      <i class="bi bi-chat"></i>
                                      <span>My Chats</span>
                                  </a>
                              </li>
                          @endcan


                        @hasrole('superadmin')     
                          <li class="{{ request()->is('logs*') ? 'active' : '' }}">
                              <a href="{{ url('logs') }}">
                                  <i class="bi bi-clock"></i>
                                  <span>Edit Logs</span>
                              </a>
                          </li>
                        @endhasrole
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
