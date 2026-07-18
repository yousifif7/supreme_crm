@extends('layouts.app')
@section('contents')
@section('title')
    {{ brand_title('General Settings') }}
@endsection
<div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">General Settings</h2>
                <p class="text-muted mb-0">Brand name and logos used across the CRM (JPG, PNG, WEBP, or SVG).</p>
                @if (session('success'))
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                @endif

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

        <div class="row d-flex justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <form action="{{ route('admin.setting.create') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Brand / product name</label>
                                <input type="hidden" name="types[]" value="website_name">
                                <input class="form-control" type="text" name="website_name"
                                    value="{{ old('website_name', get_setting('website_name') ?: brand_name()) }}"
                                    placeholder="e.g. FieldLine" maxlength="120">
                                <small class="text-muted">Shown in the sidebar, login screen, and browser title. Default product name is FieldLine (field operations line for security teams).</small>
                            </div>

                            @php
                                $accept = '.jpg,.jpeg,.png,.gif,.webp,.svg,image/svg+xml';
                                $logoFields = [
                                    'login_logo' => 'Login logo',
                                    'dashboard_logo' => 'Dashboard / sidebar logo',
                                    'avatar' => 'Default avatar',
                                    'favicon_logo' => 'Favicon',
                                ];
                            @endphp

                            @foreach ($logoFields as $key => $label)
                                <label class="form-label mt-3">{{ $label }}</label>
                                <input type="hidden" name="types[]" value="{{ $key }}">
                                <input class="form-control" type="file"
                                    accept="{{ $accept }}"
                                    name="{{ $key }}">
                                @php
                                    $current = get_setting($key);
                                    $filePath = $current ? public_path('backend/websitedata/' . $current) : null;
                                @endphp
                                @if ($current && $filePath && file_exists($filePath))
                                    <div class="thumb-container mt-2">
                                        <img src="{{ asset('backend/websitedata/' . $current) }}"
                                            class="img-thumbnail" width="100" height="100"
                                            alt="{{ $label }}"
                                            style="object-fit: contain; background: #f8f9fa;">
                                        <div class="small text-muted mt-1">{{ $current }}</div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="col-xs-12 col-sm-12 col-md-12">
                                <button type="submit" class="btn btn-primary" style="margin-top: 16px">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
