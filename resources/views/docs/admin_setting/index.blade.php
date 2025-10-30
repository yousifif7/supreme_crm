@extends('layouts.app')
@section('contents')
@section('title')
    Admin Setting
@endsection
<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">General Settings</h2>
                @if (session('success'))
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- show validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>

        </div>
        <!-- /product list -->
        <div class="row d-flex justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <form action="{{ route('admin.setting.create') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <label class="form-label">Login logo</label>
                            <input type="hidden" name="types[]" value="login_logo">
                            <input type="hidden" name="login_logo" value="{{ get_setting('login_logo') }}">
                            <input class="form-control" type="file" accept=".jpg, .png, .jpeg" name="login_logo"
                                placeholder="{{ 'login_logo ' }}" value="{{ old('login_logo') }}">
                            @php
                                $headerLogo = get_setting('login_logo');

                                $filePath = public_path('backend/websitedata/' . $headerLogo);

                            @endphp

                            @if (file_exists($filePath) && $headerLogo)
                                <div class="thumb-container">
                                    <img src="{{ asset('backend/websitedata/' . get_setting('login_logo')) }}"
                                        class="img-thumbnail mt-2" width="100px" height="100px">
                                </div>
                            @else
                            @endif


                            <label class="form-label">Dashboard logo</label>
                            <input type="hidden" name="types[]" value="dashboard_logo">
                            <input type="hidden" name="dashboard_logo" value="{{ get_setting('dashboard_logo') }}">
                            <input class="form-control" type="file" accept=".jpg, .png, .jpeg" name="dashboard_logo"
                                placeholder="{{ 'dashboard_logo ' }}" value="{{ old('dashboard_logo') }}">
                            @php
                                $dashLogo = get_setting('dashboard_logo');

                                $filePathdasg = public_path('backend/websitedata/' . $dashLogo);

                            @endphp

                            @if (file_exists($filePathdasg) && $dashLogo)
                                <div class="thumb-container">
                                    <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}"
                                        class="img-thumbnail mt-2" width="100px" height="100px">
                                </div>
                            @else
                            @endif


                            <label class="form-label">Avatar</label>
                            <input type="hidden" name="types[]" value="avatar">
                            <input type="hidden" name="avatar" value="{{ get_setting('avatar') }}">
                            <input class="form-control" type="file" accept=".jpg, .png, .jpeg" name="avatar"
                                placeholder="{{ 'avatar ' }}" value="{{ old('avatar') }}">
                            @php
                                $avatarLogo = get_setting('avatar');

                                $filePathava = public_path('backend/websitedata/' . $dashLogo);

                            @endphp

                            @if (file_exists($filePathava) && $avatarLogo)
                                <div class="thumb-container">
                                    <img src="{{ asset('backend/websitedata/' . get_setting('avatar')) }}"
                                        class="img-thumbnail mt-2" width="100px" height="100px">
                                </div>
                            @else
                            @endif

                            <label class="form-label">Favicon Logo</label>
                            <input type="hidden" name="types[]" value="favicon_logo">
                            <input type="hidden" name="favicon_logo" value="{{ get_setting('favicon_logo') }}">
                            <input class="form-control" type="file" accept=".jpg, .png, .jpeg" name="favicon_logo"
                                placeholder="{{ 'favicon_logo ' }}" value="{{ old('favicon_logo') }}">

                            @php
                                $favLogo = get_setting('favicon_logo');

                                $filePathfav = public_path('backend/websitedata/' . $dashLogo);

                            @endphp

                            @if (file_exists($filePathfav) && $favLogo)
                                <div class="thumb-container">
                                    <img src="{{ asset('backend/websitedata/' . get_setting('favicon_logo')) }}"
                                        class="img-thumbnail mt-2" width="100px" height="100px">
                                </div>
                            @else
                            @endif


                            <div class="col-xs-12 col-sm-12 col-md-12">
                                <button type="submit" class="btn btn-primary" style="margin-top: 10px">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- /product list -->
    </div>
</div>
@endsection
