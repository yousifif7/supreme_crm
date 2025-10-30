<!DOCTYPE html>
@section('title')
    {{ $digitalform->title }}
@endsection

@include('docs.partial.style')

<style>
    .form-navigation {
        margin-bottom: 30px;
    }

    .open-picker {
        border-right: 0 !important;
        border-left: 0 !important;
        border-top: 0 !important;
    }

    input {
        border-right: 0 !important;
        border-left: 0 !important;
        border-top: 0 !important;
    }

    body {
        background-color: #fff9c4;
        font-family: Arial, sans-serif;
    }

    .header {
        background-color: #212121;
        padding: 35px;
        text-align: center;
        margin: 8px 0;
        border-radius: 8px;
    }

    .logo {
        width: 100px;
        height: 100px;
    }

    .form-container {
        max-width: 650px;
        margin: 0 auto;
    }

    .form-section {
        background-color: white;
        border-radius: 8px;
        padding: 17px;
        margin-bottom: 10px;
    }

    .form-title {
        font-weight: bold;
        margin-bottom: 5px;
        display: flex;
    }

    .required:after {
        content: " *";
        color: red;
    }

    .yellow-divider {
        height: 10px;
        /* background-color: #ffd600; */
        /* margin: 0; */
        background-color: rgb(221, 200, 0);
        color: rgba(0, 0, 0, 1);
        border-radius: 6px 6px 0px 0px;
    }

    .form-footer {
        font-size: 12px;
        color: #666;
        text-align: center;
        margin-top: 20px;
    }

    .next-button {
        background-color: #4285f4;
        color: white;
    }

    .clear-button {
        color: #4285f4;
        background-color: transparent;
        border: none;
    }

    .title {
        color: rgb(32, 33, 36);
    }

    .page-indicator {
        color: #666;
        font-size: 14px;
    }

    .form-section p {
        font-weight: 400;
        font-size: 16px;
    }

    .form-check-input[type=radio] {
        border-radius: 50%;
        width: 16px !important;
        height: 16px !important;
    }

    .form-check,
    .form-check-input,
    .form-check-label {
        margin-top: 3px !important;

    }

    .form-check-input.custom-radio-black {
        border: 2px solid #0000004d !important;
        width: 1.2em;
        height: 1.2em;
        appearance: none;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
    }

    .form-check-input.custom-radio-black:checked::before {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 8px;
        height: 8px;

        border-radius: 50%;
    }

    .card .card-body .col-md-3 {
        border: 1px solid;
        padding: 13px;
    }

    input {
        border: 1px solid #000 !important;
    }

    label {
        font-weight: 700;
    }

    .table th {
        font-weight: 600;
        border: 1px solid #000;

    }

    .table td {
        font-weight: 600;
        border: 1px solid #000;

    }

    .card-body {
        padding-top: 0;
        padding-bottom: 0;
        padding-right: 14px;
        padding-left: 12px;
    }
</style>



<body>
    <form action="" method="post" enctype="multipart/form-data" id="dynamicformsubmit">
        @csrf
        <input type="hidden" value="{{ $data->id }}" name="page_id">
        <input type="hidden" value="{{ $digitalform->id }}" name="form_id">


        <div class="container">
            <div class="header">
                <h2 class="text-center p-4" style="color:#fff;font-size:3rem"><strong>Invoice</strong></h2>
            </div>

            <div class="card m-0">
                <div class="card-body">
                    <div class="row mb-4">
                        @foreach ($dynamicinput as $input)
                            @if ($input->header_status == 1)
                                <div class="col-md-3"
                                    @if ($input->value) readonly 
        style="width: 530px;" @endif>
                                    <label>{{ $input->title }}</label>

                                    @php
                                        $inputName = "name[{$input->id}]";
                                        $value = $decodedData[$input->id] ?? '';
                                    @endphp

                                    @if (in_array($input->type, ['text', 'email', 'number', 'date', 'password', 'tel', 'url']))
                                        <input type="{{ $input->type }}" class="form-control"
                                            name="{{ $inputName }}" value="{{ $value }}"
                                            placeholder="{{ $input->placeholder }}"
                                            @if ($input->value) readonly 
        style="width: 500px;" @endif>
                                    @elseif($input->type == 'decimal_value')
                                        <input type="number" step="0.01" class="form-control"
                                            name="{{ $inputName }}" value="{{ $value }}"
                                            placeholder="{{ $input->placeholder }}">
                                    @elseif($input->type === 'textarea')
                                        <textarea class="form-control" name="{{ $inputName }}" placeholder="{{ $input->placeholder }}">{{ $value }}</textarea>
                                    @elseif($input->type === 'drop')
                                        <select class="form-select" name="{{ $inputName }}">
                                            <option value="">{{ $input->title }}</option>
                                            @foreach ($input->child as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ $value == $option->id ? 'selected' : '' }}>{{ $option->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($input->type === 'radio')
                                        @foreach ($input->child as $option)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="{{ $inputName }}" value="{{ $option->title }}"
                                                    {{ $value == $option->title ? 'checked' : '' }}>
                                                <label class="form-check-label">{{ $option->title }}</label>
                                            </div>
                                        @endforeach
                                    @elseif($input->type === 'checkbox')
                                        @php $checkedValues = is_array($value) ? $value : []; @endphp
                                        @foreach ($input->child as $option)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="{{ $inputName }}[]" value="{{ $option->title }}"
                                                    {{ in_array($option->title, $checkedValues) ? 'checked' : '' }}>
                                                <label class="form-check-label">{{ $option->title }}</label>
                                            </div>
                                        @endforeach
                                    @elseif($input->type === 'file')
                                        <input type="file" class="form-control" name="{{ $inputName }}[]"
                                            multiple>
                                    @elseif($input->type === 'time')
                                        <input type="time" class="form-control datetimepicker"
                                            name="{{ $inputName }}" value="{{ $value }}">
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>



            @php $perPage = 10; @endphp

            <table class="table table-bordered">
                <thead>
                    <tr>
                        @foreach ($dynamicinput as $input)
                            @if ($input->header_status == 0)
                                <th>{{ $input->title }}</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < 31; $i++)
                        <tr>
                            @foreach ($dynamicinput as $input)
                                @if ($input->header_status == 0)
                                    <td>
                                        @php
                                            $inputName = "name[{$input->id}][{$i}]";
                                            $value = $decodedData[$input->id][$i] ?? '';
                                        @endphp

                                        @if (in_array($input->type, ['text', 'email', 'number', 'date', 'password', 'tel', 'url']))
                                            <input type="{{ $input->type }}" class="form-control"
                                                name="{{ $inputName }}" value="{{ $value }}"
                                                placeholder="{{ $input->placeholder }}">
                                        @elseif($input->type == 'decimal_value')
                                            <input type="number" step="0.01" class="form-control"
                                                name="{{ $inputName }}" value="{{ $value }}"
                                                placeholder="{{ $input->placeholder }}">
                                        @elseif($input->type === 'textarea')
                                            <textarea class="form-control" name="{{ $inputName }}" placeholder="{{ $input->placeholder }}">{{ $value }}</textarea>
                                        @elseif($input->type === 'drop')
                                            <select class="form-select" name="{{ $inputName }}">
                                                <option value="">{{ $input->title }}</option>
                                                @foreach ($input->child as $option)
                                                    <option value="{{ $option->id }}"
                                                        {{ $value == $option->id ? 'selected' : '' }}>
                                                        {{ $option->title }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($input->type === 'radio')
                                            @foreach ($input->child as $option)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="{{ $inputName }}" value="{{ $option->title }}"
                                                        {{ $value == $option->title ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ $option->title }}</label>
                                                </div>
                                            @endforeach
                                        @elseif($input->type === 'checkbox')
                                            @php $checkedValues = is_array($value) ? $value : []; @endphp
                                            @foreach ($input->child as $option)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="{{ $inputName }}[]" value="{{ $option->title }}"
                                                        {{ in_array($option->title, $checkedValues) ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ $option->title }}</label>
                                                </div>
                                            @endforeach
                                        @elseif($input->type === 'file')
                                            <input type="file" class="form-control" name="{{ $inputName }}[]"
                                                multiple>
                                        @elseif($input->type === 'time')
                                            <input type="time" class="form-control datetimepicker"
                                                name="{{ $inputName }}" value="{{ $value }}">
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @foreach ($dynamicinput as $input)
                            @if ($input->header_status == 2)
                                <div class="col-md-2">
                                    <label>{{ $input->title }}</label>

                                    @php
                                        $inputName = "name[{$input->id}]";
                                        $value = $decodedData[$input->id] ?? '';
                                    @endphp

                                    @if (in_array($input->type, ['text', 'email', 'number', 'date', 'password', 'tel', 'url']))
                                        <input type="{{ $input->type }}" class="form-control"
                                            name="{{ $inputName }}" value="{{ $value }}"
                                            placeholder="{{ $input->placeholder }}">
                                    @elseif($input->type == 'decimal_value')
                                        <input type="number" step="0.01" class="form-control"
                                            name="{{ $inputName }}" value="{{ $value }}"
                                            placeholder="{{ $input->placeholder }}">
                                    @elseif($input->type === 'textarea')
                                        <textarea class="form-control" name="{{ $inputName }}" placeholder="{{ $input->placeholder }}">{{ $value }}</textarea>
                                    @elseif($input->type === 'drop')
                                        <select class="form-select" name="{{ $inputName }}">
                                            <option value="">{{ $input->title }}</option>
                                            @foreach ($input->child as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ $value == $option->id ? 'selected' : '' }}>{{ $option->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($input->type === 'radio')
                                        @foreach ($input->child as $option)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="{{ $inputName }}" value="{{ $option->title }}"
                                                    {{ $value == $option->title ? 'checked' : '' }}>
                                                <label class="form-check-label">{{ $option->title }}</label>
                                            </div>
                                        @endforeach
                                    @elseif($input->type === 'checkbox')
                                        @php $checkedValues = is_array($value) ? $value : []; @endphp
                                        @foreach ($input->child as $option)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="{{ $inputName }}[]" value="{{ $option->title }}"
                                                    {{ in_array($option->title, $checkedValues) ? 'checked' : '' }}>
                                                <label class="form-check-label">{{ $option->title }}</label>
                                            </div>
                                        @endforeach
                                    @elseif($input->type === 'file')
                                        <input type="file" class="form-control" name="{{ $inputName }}[]"
                                            multiple>
                                    @elseif($input->type === 'time')
                                        <input type="time" class="form-control" name="{{ $inputName }}"
                                            value="{{ $value }}">
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="row d-flex justify-content-center" style="background-color:yellow">
                        <div class="col-md-6">
                            <p class="p-1 m-0" style="text-align:center"><strong>I'm Responsible for paying my own
                                    National insurance and Tax.</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($digitalform->desc)
                <div class="form-section d-none extra-desc">{!! $digitalform->desc !!}</div>
            @endif

            @if ($digitalform->paginate_status == 1)
                <div class="form-navigation d-flex align-items-center justify-content-between flex-wrap mt-3">
                    <button type="button" id="prevPage" class="btn btn-secondary mb-2">Previous</button>
                    <div id="pageNumbers" class="d-flex flex-wrap gap-1 mx-2 mb-2"></div>
                    <button type="button" id="nextPage" class="btn btn-primary mb-2">Next</button>
                    <button type="submit" id="submitForm" style="display:none;"
                        class="btn btn-success mb-2">Submit</button>
                </div>
            @else
                <div class="form-navigation text-center mt-3">
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            @endif

        </div> {{-- Close last page --}}
    </form>


    @include('docs.partial.script')
    @can('client detail edit')
        <script>
            $(document).ready(function() {
                $('#dynamicformsubmit').on('submit', function(e) {
                    e.preventDefault();

                    let formData = new FormData(this);

                    $.ajax({
                        url: "{{ route('client.complete.detail.update', $id) }}",
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function() {
                            $('.form-control').removeClass('is-invalid');
                            $('.invalid-feedback').remove();
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Data has been sent successfully');
                                $('#dynamicformsubmit')[0].reset();
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;

                                $.each(errors, function(key, value) {
                                    let inputName = key.replace('.', '[') + ']';
                                    let inputField = $('[name="' + inputName + '"]');

                                    if (inputField.length) {
                                        inputField.addClass('is-invalid');
                                        if (inputField.next('.invalid-feedback').length ===
                                            0) {
                                            inputField.after(
                                                '<div class="invalid-feedback">' +
                                                value[0] + '</div>');
                                        }
                                    }
                                });
                            } else {
                                console.log(xhr.responseJSON);
                            }
                        }
                    });
                });
            });
        </script>


        <script>
            function toggleOtherField(inputId, show) {
                let otherField = $('#' + inputId);
                if (show) {
                    otherField.removeClass('d-none');
                } else {
                    otherField.addClass('d-none').val(''); // Hide and clear input
                }
            }

            $(document).ready(function() {
                $('input[type="radio"]').on('change', function() {
                    let otherFieldId = $(this).closest('.form-section').find('.other-input').attr('id');
                    if ($(this).val() === 'other') {
                        toggleOtherField(otherFieldId, true);
                    } else {
                        toggleOtherField(otherFieldId, false);
                    }
                });
            });
        </script>


        <!-- Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            $(document).ready(function() {
                $(".datetimepicker").each(function() {
                    let picker = $(this).flatpickr({
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        time_24hr: true,
                        allowInput: true,
                        onClose: function(selectedDates, dateStr, instance) {
                            instance.close();
                        }
                    });

                    $(this).siblings(".open-picker").on("click", function() {
                        picker.open();
                    });
                });
            });
        </script>



        <script>
            $(document).ready(function() {
                $(".delete-file").click(function() {
                    let button = $(this);
                    let inputId = button.data("input");
                    let index = button.data("index");
                    let filePath = button.data("file");

                    if (confirm("Are you sure you want to delete this file?")) {
                        $.ajax({
                            url: "{{ route('delete.file') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                input_id: inputId,
                                index: index,
                                file: filePath
                            },
                            success: function(response) {
                                if (response.success) {
                                    $("#file-" + inputId + "-" + index).remove();
                                } else {
                                    alert("Failed to delete file.");
                                }
                            },
                            error: function(xhr) {
                                alert("Error deleting file. Please try again.");
                            }
                        });
                    }
                });
            });
        </script>
    @endcan
</body>

</html>
