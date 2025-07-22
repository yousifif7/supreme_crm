@extends('layouts.app')
@section('title', 'CRM - Worker Calendar')

@section('styles')
    <style>
        .datepic .fc-prev-button,
        .datepic .fc-next-button {
            font-size: 12px !important;
            /* Reduce arrow icon size */
            padding: 2px 4px !important;
            /* Reduce button padding */
            height: 24px !important;
            /* Reduce button height */
            width: 30px !important;
            /* Optional: make buttons smaller square */
        }
    </style>
@endsection
@section('contents')
    <!-- Page Wrapper -->
    <div id="scheduling" class="page-wrapper site_calendar">
        <div class="content">
            <div class="alert-box-container"></div>
            <!-- Breadcrumb -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Scheduling</h2>

                </div>

            </div>


            @include('security_boards.shiftfilter')

            <div class="row" style="padding-right: 0px !important; padding-left: 0px !important;">

                <!-- Calendar Sidebar -->
                <div class="col-xxl-2 col-xl-3" style="padding-right: 0px !important; padding-left: 0px !important;">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="border-bottom pb-2 mb-4">
                                <div class="datepic"></div>
                            </div>

                            <!-- Event -->
                            <div class="border-bottom pb-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5>Event </h5>
                                    <a href="#" class="link-primary" data-bs-toggle="modal"
                                        data-bs-target="#add_event"><i
                                            class="ti ti-square-rounded-plus-filled fs-16"></i></a>
                                </div>
                                <p class="fs-12 mb-2">Drag and drop your event or click in the calendar</p>
                                <div id='external-events'>
                                    <div class="fc-event bg-dark-blue mb-1" data-event='{ "title": "Pending" }'
                                        data-event-classname="bg-dark-blue">
                                        Pending
                                    </div>
                                    <div class="fc-event bg-lighter mb-1" data-event='{ "title": "Dispatched" }'
                                        data-event-classname="bg-lighter">
                                        Dispatched
                                    </div>
                                    <div class="fc-event bg-dark-green mb-1" data-event='{ "title": "Accepted" }'
                                        data-event-classname="bg-dark-green">
                                        Accepted
                                    </div>
                                    <div class="fc-event bg-light-yellow mb-1" data-event='{ "title": "Started" }'
                                        data-event-classname="bg-light-yellow">
                                        Started
                                    </div>
                                    <div class="fc-event bg-light-blue mb-1" data-event='{ "title": "Ended" }'
                                        data-event-classname="bg-light-blue">
                                        Ended
                                    </div>
                                    <div class="fc-event bg-purple mb-0" data-event='{ "title": "Rejected" }'
                                        data-event-classname="bg-purple">
                                        Rejected
                                    </div>
                                    <div class="fc-event bg-red mb-0" data-event='{ "title": "Cancelled" }'
                                        data-event-classname="bg-red">
                                        Cancelled
                                    </div>
                                    <div class="fc-event bg-dark-yellow mb-0" data-event='{ "title": "Pre-Start" }'
                                        data-event-classname="bg-primary">
                                        Pre-Start
                                    </div>
                                    <div class="fc-event bg-orange mb-0" data-event='{ "title": "Await-Finish" }'
                                        data-event-classname="bg-orange">
                                        Await-Finish
                                    </div>
                                </div>
                            </div>
                            <!-- /Event -->



                        </div>
                    </div>

                </div>
                <!-- /Calendar Sidebar -->

                <div class="col-xxl-10 col-xl-9 theiaStickySidebar">
                    <div class="card border-0">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Calendar View -->

            <!-- Add Rota -->

            <div class="modal fade" id="add_rota">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Add New Rota</h4>
                            <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <form action="#">
                            <div class="contact-grids-tab">
                                <ul class="nav nav-underline" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab"
                                            data-bs-target="#basic-info" type="button" role="tab"
                                            aria-selected="true">Basic Information</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="address-tab" data-bs-toggle="tab"
                                            data-bs-target="#address" type="button" role="tab"
                                            aria-selected="false">Address</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="social-profile-tab" data-bs-toggle="tab"
                                            data-bs-target="#social-profile" type="button" role="tab"
                                            aria-selected="false">Social Profiles</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="access-tab" data-bs-toggle="tab"
                                            data-bs-target="#access" type="button" role="tab"
                                            aria-selected="false">Access</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="schedule-tab" data-bs-toggle="tab"
                                            data-bs-target="#schedule" type="button" role="tab"
                                            aria-selected="false">Scheduling</button>
                                    </li>

                                </ul>
                            </div>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                                    aria-labelledby="info-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div
                                                    class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">
                                                    <div
                                                        class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
                                                        <i class="ti ti-photo text-gray-2 fs-16"></i>
                                                    </div>
                                                    <div class="profile-upload">
                                                        <div class="mb-2">
                                                            <h6 class="mb-1">Upload Profile Image</h6>
                                                            <p class="fs-12">Image should be below 4 mb</p>
                                                        </div>
                                                        <div class="profile-uploader d-flex align-items-center">
                                                            <div class="drag-upload-btn btn btn-sm btn-primary me-2">
                                                                Upload
                                                                <input type="file" class="form-control image-sign"
                                                                    multiple="">
                                                            </div>
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-light btn-sm">Cancel</a>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Company Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number 2</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Fax</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Website</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Ratings <span class="text-danger">
                                                            *</span></label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-star text-gray-6"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Owner <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Hendry Milner</option>
                                                        <option>Guilory Berggren</option>
                                                        <option>Jami Carlile</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Tags <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input-tags form-control" placeholder="Add new"
                                                        type="text" data-role="tagsinput" name="Label"
                                                        value="Collab">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label class="col-form-label p-0">Deals <span
                                                                class="text-danger">*</span></label>
                                                        <a href="#" class="add-new text-primary"
                                                            data-bs-target="#add_deals" data-bs-toggle="modal"><i
                                                                class="ti ti-plus text-primary me-1"></i>Add New</a>
                                                    </div>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Collins</option>
                                                        <option>Konopelski</option>
                                                        <option>Adams</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Industry <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Retail Industry</option>
                                                        <option>Banking</option>
                                                        <option>Hotels</option>
                                                        <option>Financial Services</option>
                                                        <option>Insurance</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Source <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Phone Calls</option>
                                                        <option>Social Media</option>
                                                        <option>Refferal Sites</option>
                                                        <option>Web Analytics</option>
                                                        <option>Previous Purchase</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Currency <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>USD</option>
                                                        <option>Euro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 ">
                                                    <label class="form-label">Language <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>English</option>
                                                        <option>Arabic</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-3 ">
                                                    <label class="form-label">About <span
                                                            class="text-danger">*</span></label>
                                                    <textarea class="form-control"></textarea>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Address <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Country <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>USA</option>
                                                        <option>Canada</option>
                                                        <option>Germany</option>
                                                        <option>France</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">State <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>California</option>
                                                        <option>New York</option>
                                                        <option>Texas</option>
                                                        <option>Florida</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">City <span class="text-danger">
                                                            *</span></label>
                                                    <select class="form-select">
                                                        <option>Select</option>
                                                        <option>Los Angeles</option>
                                                        <option>San Diego</option>
                                                        <option>Fresno</option>
                                                        <option>San Francisco</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Zipcode <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="social-profile" role="tabpanel"
                                    aria-labelledby="social-profile-tab" tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Facebook</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Twitter</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">LinkedIn</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Skype</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Whatsapp</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Instagram</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="access" role="tabpanel" aria-labelledby="access-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="mb-4">
                                            <h6 class="fs-14 fw-medium mb-1">Visibility</h6>
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault01">
                                                    <label class="form-check-label text-dark" for="flexRadioDefault01">
                                                        Public
                                                    </label>
                                                </div>
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault02" checked>
                                                    <label class="form-check-label text-dark" for="flexRadioDefault02">
                                                        Private
                                                    </label>
                                                </div>
                                                <div class="form-check ">
                                                    <input class="form-check-input" type="radio"
                                                        name="flexRadioDefault" id="flexRadioDefault03" checked>
                                                    <label class="form-check-label text-dark" for="flexRadioDefault03">
                                                        Select People
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-gray br-5 mb-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-06">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-01.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Michael Walker</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-07">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-02.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Sophie Headrick</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-08">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-03.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Cameron Drake</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-09">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/reports/user-04.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Doris Crowley</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <input class="form-check-input me-1" type="checkbox" value=""
                                                    id="user-11">
                                                <div class="d-flex align-items-center file-name-icon">
                                                    <a href="#" class="avatar avatar-md border avatar-rounded">
                                                        <img src="https://smarthr.co.in/demo/html/template/assets/img/profiles/avatar-12.jpg"
                                                            class="img-fluid" alt="img">
                                                    </a>
                                                    <div class="ms-2">
                                                        <h6 class="fw-normal"><a href="#">Thomas Bordelon</a></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <a href="#" class="btn btn-primary">Confirm</a>
                                            </div>
                                        </div>
                                        <div class="mb-3 ">
                                            <label class="form-label">Status</label>
                                            <select class="form-select">
                                                <option>Select</option>
                                                <option>Active</option>
                                                <option>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#success_compay">Save </button>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="access-tab"
                                    tabindex="0">
                                    <div class="modal-body pb-0 ">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Name</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Date</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control datetimepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-calendar text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Time</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Time</label>
                                                    <div class="input-icon-end position-relative">
                                                        <input type="text" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Location</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label">Descriptions</label>
                                                    <textarea class="form-control" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#success_compay">Save </button>
                                    </div>
                                </div>

                            </div>


                        </form>
                    </div>
                </div>
            </div>
            <!-- Add shift -->
            @include('security_boards.shiftmodal');
            <!-- /Breadcrumb -->


        </div>

        <!-- Add Shift Success -->
        <div class="modal fade" id="success_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center p-3">
                            <span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
                                    class="ti ti-check fs-24"></i></span>
                            <h5 class="mb-2" id="success_message"></h5>

                            </p>
                            <div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="{{ url('worker_calendar') }}" class="btn btn-dark w-100">Back to
                                            List</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Shift Modal -->
        @include('security_boards.assign-shift-modal')

    </div>

    <!-- /Page Wrapper -->
@endsection
@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initDaySelector(shiftGroup) {
                const dayBoxes = shiftGroup.querySelectorAll('.day-box');
                const hiddenInput = shiftGroup.querySelector('input[name="days[]"]');

                dayBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        box.classList.toggle('selected');
                        const selected = Array.from(shiftGroup.querySelectorAll(
                                '.day-box.selected'))
                            .map(el => el.getAttribute('data-day'));

                        hiddenInput.value = selected.join(',');
                    });
                });
            }

            function bindEvents() {
                // Add Shift Button
                document.querySelectorAll('.addShiftGroup').forEach(btn => {
                    btn.onclick = function() {
                        const wrapper = document.querySelector('.shift-wrapper');
                        const lastGroup = wrapper.querySelector('.shift-group:last-of-type');
                        const clone = lastGroup.cloneNode(true);

                        // Reset values in clone
                        clone.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        });

                        // Reset day selection
                        clone.querySelectorAll('.day-box').forEach(box => box.classList.remove(
                            'selected'));
                        clone.querySelector('input[name="days[]"]').value = '';

                        // Update data-shift-group attribute
                        const allShiftGroups = wrapper.querySelectorAll('.shift-group');
                        const newShiftGroupIndex = allShiftGroups.length;
                        const checkpointBtn = clone.querySelector('.addCheckpointRow');
                        if (checkpointBtn) {
                            checkpointBtn.setAttribute('data-shift-group', newShiftGroupIndex);
                        }
                        const checkpointSection = clone.querySelector('.checkpoint-section');
                        if (checkpointSection) {
                            checkpointSection.setAttribute('id', `checkpoint-section${newShiftGroupIndex}`);
                        }

                        // Clear checkpoint rows
                        const checkpointRows = clone.querySelector('.checkpoint-rows');
                        if (checkpointRows) {
                            checkpointRows.innerHTML = '';
                        }

                        wrapper.appendChild(clone);

                        // Re-init new shift group logic
                        initDaySelector(clone);
                        bindEvents();
                    };
                });

                // Remove Shift Button
                document.querySelectorAll('.remove-shift').forEach(btn => {
                    btn.onclick = function() {
                        const shiftGroups = document.querySelectorAll('.shift-wrapper .shift-group');
                        if (shiftGroups.length > 1) {
                            btn.closest('.shift-group').remove();
                        } else {
                            toast_danger('You must have at least one shift.');
                        }
                    };
                });
            }

            // Initialize for first shift-group
            document.querySelectorAll('.shift-group').forEach(group => initDaySelector(group));

            // Initial binding
            bindEvents();
        });

        let checkIndex = 0;
        function addCheckpointRow($parentRow, groupIndex = 0) {
            checkIndex++;

            const checkpointRow = `
                <div class="row checkpoint-row mb-3 align-items-center" data-index="${checkIndex}">
                    <div class="col-md-3"><label>Checkpoint Name</label>
                        <input type="text" name="checkpoints[${groupIndex}][${checkIndex}][checkpoint_name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Time</label>
                        <input type="time" name="checkpoints[${groupIndex}][${checkIndex}][checkpoint_time]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm removeCheckpointRow">Remove</button>
                    </div>
                </div>
            `;

            $parentRow.append(checkpointRow);
            // $('#checkpoint-rows').append(checkpointRow);
        }

        $(document).on('click', '.addCheckpointRow', function() {
            var groupIndex = $(this).data('shift-group');
            var $parentRow = $(this).parents(`#checkpoint-section${groupIndex}`).find('.checkpoint-rows');
            addCheckpointRow($parentRow, groupIndex);
        });
        $(document).on('click', '.removeCheckpointRow', function() {
            $(this).closest('.checkpoint-row').remove();
        });
        
    </script>
    <script>
        $(document).ready(function() {
            $('#add_shift-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this)[0];
                let formData = new FormData(form);
                let submitButton = $('#saveshift'); // Add an ID to your submit button

                // Disable button and show loading
                submitButton.prop('disabled', true).html('Saving...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        closeBsModal('#add_shift');
                        $('#success_message').html('Shift Added Successfully')
                        $('#success_modal').modal('show');
                    },
                    error: function(xhr) {
                        console.log("Status:", xhr.status);
                        console.log("Response:", xhr.responseText); // Helpful for debugging

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#error_' + key).text(value[0]);
                            });
                        } else if (xhr.responseJSON?.error) {
                            toast_danger(xhr.responseJSON.error); //
                        } else {
                            toast_danger('An unexpected error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        // Re-enable button after response
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });
        });
    </script>
    <!-- Inline Scripts after libraries load -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const colorMap = {
                'bg-dark-blue': '#5489C4',
                'bg-lighter': '#D6D4CE',
                'bg-dark-green': '#69CF83',
                'bg-light-yellow': '#FAD66B',
                'bg-light-blue': '#80BFFF',
                'bg-purple': '#9F87F5',
                'bg-red': '#F55B7C',
                'bg-primary11': '#FFFF5E',
                'bg-orange': '#F5B25F',
                'bg-secondary': '#6c757d'
            };

            fetch(`${baseUrl}/api/shifts-with-staff`)
                .then(response => response.json())
                .then(data => {
                    const highlightDates = data.highlightDates;

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: new Date().toISOString().split('T')[0],
                        timeZone: 'local',
                        eventDisplay: 'block',
                        displayEventEnd: true,

                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,dayGridWeek,dayGridDay'
                        },

                        events: data.events,

                        // 🔸 Highlight days with shifts
                        dayCellClassNames: function(arg) {
                            const dateStr = arg.date.toLocaleDateString('en-CA'); // YYYY-MM-DD
                            return highlightDates.includes(dateStr) ? ['highlight-day'] : [];
                        },

                        // 🔸 Render events with images, icons, time, and urgency
                        eventContent: function(info) {
                            const event = info.event;
                            const props = event.extendedProps;

                            const startTime = event.start?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';
                            const endTime = event.end?.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            }) || '';

                            const container = document.createElement('div');

                            // Compose class string
                            container.className =
                                `fc-custom-event ${props.className || 'bg-secondary'} ${props.urgent ? 'urgent-ring' : ''}`;

                            // Apply background color from colorMap based on className
                            const bgClass = event.classNames?.[0] || 'bg-secondary';
                            const bgColor = colorMap[bgClass] || colorMap['bg-secondary'];


                            container.innerHTML = `
                        <div class="d-flex align-items-center gap-1" style="background: ${bgColor} !important;">
                            <div class="position-relative">
                                ${props.urgent ? '<span class="urgent-indicator bg-danger"></span>' : ''}
                            </div>
                            <div class="flex-grow-1">
                                <div><strong style="color: black;">${event.title}</strong></div>
                                <div class="text-muted" style="font-size: 0.75rem; color: black !important;">${props.location || ''}</div>
                                <div class="text-muted" style="font-size: 0.75rem; color: black !important">${startTime} - ${endTime}</div>
                            </div>
                        </div>
                    `;

                            return {
                                domNodes: [container]
                            };
                        },

                        eventClick: function(info) {
                            // console.log('Event clicked:', info.event.extendedProps);
                            // create a button with data-toggle="ajax-modal" in body and click it
                            const button = document.createElement('button');
                            button.setAttribute('data-toggle', 'ajax-modal');
                            button.setAttribute('data-title', 'Rota Detail');
                            button.setAttribute('data-size', 'modal-xl');
                            button.setAttribute('data-width', '80%');
                            button.setAttribute('data-href', `shifts/${info.event.extendedProps.sd_id}`);
                            button.style.display = 'none';
                            document.body.appendChild(button);
                            button.click();
                        },

                        eventDidMount: function(info) {
                            info.el.style.overflow = 'visible';
                        }
                    });

                    calendar.render();

                    $('#calendarSearch').on('input', function() {
                        const searchText = $(this).val().toLowerCase();

                        calendar.batchRendering(() => {
                            calendar.getEvents().forEach(event => {
                                const matches = event.title.toLowerCase().includes(searchText) ||
                                                (event.extendedProps.location && event.extendedProps.location.toLowerCase().includes(searchText));

                                if (matches) {
                                    event.setProp('display', 'auto'); // show event
                                } else {
                                    event.setProp('display', 'none'); // hide event
                                }
                            });
                        });
                    });
                    
                    // 🔸 Sidebar mini calendar
                    const sidebarEl = document.querySelector('.datepic');
                    if (sidebarEl) {
                        const sidebarCal = document.createElement('div');
                        sidebarEl.appendChild(sidebarCal);

                        new FullCalendar.Calendar(sidebarCal, {
                            initialView: 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev',
                                center: 'title',
                                right: 'next'
                            },
                            selectable: true,
                            dateClick: function(info) {
                                calendar.gotoDate(info.dateStr);
                            },
                            height: 'auto',
                            initialDate: new Date().toISOString().split('T')[0],
                            timeZone: 'local'
                        }).render();
                    }
                });
        });
    </script>



    <script>
        document.querySelectorAll('.numeric-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');

                // Optional: Only allow one decimal point
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts[1];
                }
            });
        });
    </script>
    <script type="text/javascript">
        $(document).on("change","#clientSelect",function() {
            var $this = $(this);
            const clientId = $(this).val();

            if (!clientId) return;

            var $siteSelect = $('#siteSelect');
            // Clear current options
            $siteSelect.html('<option value="">--choose--</option>');
            
            $.ajax({
                url: `${baseUrl}/api/client/${clientId}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    $this.parents('.shift-group').find('.siteRate').val(data.client.office_rate || '');

                    if (data.sites && data.sites.length > 0) {
                        $.each(data.sites, function (index, site) {
                            $siteSelect.append('<option value="' + site.id + '">' + site.site_name + '</option>');
                        });
                    } else {
                        $siteSelect.append('<option value="">No sites found</option>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });

        $(document).on("change","#StaffSelect",function() {
            var $this = $(this);
            const staffId = $(this).val();

            if (!staffId) return;

            $.ajax({
                url: `${baseUrl}/api/staff/${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    $this.parents('.shift-group').find('.staffRate').val(data.employee.guard_rate || '');
                },
                error: function (xhr, status, error) {
                    console.error('Fetch error:', error);
                }
            });
        });
    </script>
@endsection
