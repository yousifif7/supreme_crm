<!-- Filter Modal -->

@php
    $shifts = App\Models\Shift::with(['staff', 'site'])->get();
    // $staffIds = $shifts->pluck('staff_id')->unique();
    // $siteIds = $shifts->pluck('site_id')->unique();

    $staffs = App\Models\User::role('security_staff')->get();
    $sites = App\Models\Site::all();
     $clients = App\Models\User::role('client')->get();
@endphp

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="shiftFilterForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Shifts</h5>
                     <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Staff -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Staff</label>
                            <select class="form-select staff-select-filter" name="staff">
                                <option value="">--choose--</option>
                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                         <div class="col-md-4 mb-3">
                            <label class="form-label">Client</label>
                            <select class="form-select client-select-filter" name="client_id">
                                <option value="">--choose--</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->first_name }} {{$client->last_name  }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Site -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Site</label>
                            <select class="form-select site-select-filter" name="site">
                                <option value="">--choose--</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>

                       <div class="col-md-4 mb-3">
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
        <option value="">--choose--</option>
        @php
            $statusLabels = [
                0 => 'Pending',
                1 => 'Dispatched',
                2 => 'Accepted',
                3 => 'Started',
                4 => 'Ended',
                5 => 'Rejected',
                6 => 'Cancelled',
                7 => 'Pre-start',
                8 => 'Await-finish',
            ];
        @endphp
        @foreach ($statusLabels as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </select>
</div>


                        <!-- Start Time -->
                      

                        <!-- Created At -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="from_shift">
                        </div>
                         <div class="col-md-4 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="to_shift">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="add_btn btn btn-white">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>

