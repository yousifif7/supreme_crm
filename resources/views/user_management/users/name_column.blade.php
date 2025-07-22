<div class="d-flex align-items-center file-name-icon">
    <a onclick="viewUserDetail({{ $user->id }})"
        class="avatar avatar-md border avatar-rounded">
        <img src="{{ $user->profile_picture ? asset('uploads/profile_pictures/' . $user->profile_picture) : asset('uploads/no.png') }}"
            class="img-fluid" alt="Profile Picture">
    </a>
    <div class="ms-2">
        <h6 class="fw-medium">
            <a onclick="viewUserDetail({{ $user->id }})">
                {{ $user->first_name }} {{ $user->last_name }}
            </a>
        </h6>
        <span class="fs-10 fw-normal">
            <i class="ti ti-phone"></i>&nbsp;{{ $user->phone_number }}&nbsp;
            <a href="#"><i class="ti ti-external-link"></i></a>
        </span>
    </div>
</div>
