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
</style>


<body>
    <form action="" method="post" enctype="multipart/form-data" id="dynamicformsubmit" autocomplete="off">
        @csrf
        <input type="hidden" value="{{ $data->id }}" name="page_id">
        <input type="hidden" value="{{ $digitalform->id }}" name="form_id">
        <input type="hidden" id="totalPages" value="{{ ceil(count($dynamicinput) / 10) }}">

        <div class="form-container">
            <div class="header">
                <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="Logo"
                    class="logo">
            </div>
            <div class="yellow-divider"></div>

            <div class="form-section">
                <h1 class="title fw-bold">{{ $data->title }}</h1>
                <p>{!! $data->desc !!}</p>
            </div>

            @php $perPage = 10; @endphp

            @foreach ($dynamicinput as $index => $input)
                @if ($index % $perPage == 0)
                    @if ($index != 0)
        </div>
        @endif
        @php $pageNumber = floor($index / $perPage) + 1; @endphp
        <div class="form-page" id="page-{{ $pageNumber }}" style="display: none;">
            @endif

            <div class="form-section" @if ($input->type === 'heading') style="padding:0" @endif>
                @if ($input->type === 'heading')
                    <div class="card-header" style="background-color: rgb(223, 199, 0); padding: 13px 18px 4px 8px">
                        <h3 style="font-weight: 600;text-decoration: underline;font-size:15px">{{ $input->title }}</h3>
                    </div>
                    @if ($input->desc)
                        <p style="padding:15px">{!! $input->desc !!}</p>
                    @endif
                @elseif($input->label_status == 1)
                    <label class="form-title @if ($input->required == 1) required @endif"
                        @if (strtolower($input->type) === 'paragraph') style="display:block;" @endif>
                        <p>{!! $input->title !!}</p>
                    </label>
                @endif

                @if (in_array($input->type, ['text', 'email', 'number', 'date', 'password', 'tel', 'url']))
                    <input type="{{ $input->type }}" class="form-control" name="name[{{ $input->id }}]"
                        placeholder="{{ $input->placeholder }}">
                @elseif($input->type == 'decimal_value')
                    <input type="number" step="0.01" class="form-control decimal-input"
                        name="name[{{ $input->id }}]" placeholder="{{ $input->placeholder }}"
                        value="{{ isset($input->value) ? number_format($input->value, 2, '.', '') : '' }}">
                @elseif($input->type === 'textarea')
                    <textarea class="form-control" name="name[{{ $input->id }}]" placeholder="{{ $input->placeholder }}"></textarea>
                @elseif($input->type === 'drop')
                    <select class="form-select" name="name[{{ $input->id }}]">
                        <option value="">{{ $input->title }}</option>
                        @foreach ($input->child as $option)
                            <option value="{{ $option->id }}">{{ $option->title }}</option>
                        @endforeach
                    </select>
                @elseif($input->type === 'radio')
                    @foreach ($input->child as $option)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input custom-radio-black" type="radio"
                                name="name[{{ $input->id }}]" value="{{ $option->title }}"
                                id="flexCheckDefault{{ $option->id }}">
                            <label class="form-check-label"
                                for="flexCheckDefault{{ $option->id }}">{{ $option->title }}</label>
                        </div>
                    @endforeach
                @elseif($input->type === 'checkbox')
                    @foreach ($input->child as $child)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="name[{{ $input->id }}][]"
                                value="{{ $child->title }}">
                            <label class="form-check-label">{{ $child->title }}</label>
                        </div>
                    @endforeach
                @elseif($input->type === 'file')
                    <input type="file" class="form-control" name="name[{{ $input->id }}][]" multiple>
                @elseif($input->type === 'time')
                    <div class="container">
                        <div class="input-group">
                            <input type="text" id="datetimepicker{{ $input->id }}"
                                class="form-control datetimepicker" placeholder="Select Time"
                                name="name[{{ $input->id }}]">
                        </div>
                    </div>
                @endif
            </div>
            @endforeach
        </div> {{-- Close last page --}}

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
        </div>
    </form>


    @include('docs.partial.script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let totalPages = $(".form-page").length;

            function generatePageNumbers() {
                $("#pageNumbers").empty();
                for (let i = 1; i <= totalPages; i++) {
                    $("#pageNumbers").append(
                        `<button type="button" class="btn btn-sm btn-outline-primary page-btn" data-page="${i}">${i}</button>`
                    );
                }
                updatePageButtons();
            }

            function updatePageButtons() {
                $(".page-btn").removeClass("active btn-primary").addClass("btn-outline-primary");
                $(`.page-btn[data-page='${currentPage}']`).addClass("active btn-primary");
            }

            function showPage(page) {
                $(".form-page").hide();
                $("#page-" + page).show();
                $("#prevPage").toggle(page > 1);
                $("#nextPage").toggle(page < totalPages);
                $("#submitForm").toggle(page === totalPages);
                updatePageButtons();

                if (page === totalPages) {
                    $('.extra-desc').removeClass('d-none');
                } else {
                    $('.extra-desc').addClass('d-none');
                }
            }


            $("#nextPage").on("click", function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    showPage(currentPage);
                }
            });

            $("#prevPage").on("click", function() {
                if (currentPage > 1) {
                    currentPage--;
                    showPage(currentPage);
                }
            });

            $(document).on("click", ".page-btn", function() {
                currentPage = parseInt($(this).data("page"));
                showPage(currentPage);
            });

            function findFirstErrorPage() {
                let firstErrorPage = null;
                $(".form-page").each(function(index) {
                    if ($(this).find('.is-invalid').length > 0) {
                        firstErrorPage = index + 1;
                        return false;
                    }
                });
                return firstErrorPage;
            }

            $('#dynamicformsubmit').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('digital.form.submit') }}",
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
                            Swal.fire({
                                title: 'Thank you for submitting this form!',
                                text: 'Click OK to submit another',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#dynamicformsubmit')[0].reset();
                            $('.file-preview-container').remove();
                            currentPage = 1;
                            showPage(currentPage);
                        }
                    },
                    error: function(xhr) {
                        let errorcheck = xhr.responseJSON.errors;
                        console.log(errorcheck);
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

                            let firstErrorPage = findFirstErrorPage();
                            if (firstErrorPage) {
                                currentPage = firstErrorPage;
                                showPage(currentPage);
                            }
                        } else {
                            console.log(xhr.responseJSON);
                        }
                    }
                });
            });

            generatePageNumbers();
            showPage(currentPage);
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
            $('input[type="file"]').on("change", function(event) {
                let fileInput = $(this)[0];
                let fileList = fileInput.files;
                let fileContainer = $(this).next(".file-preview-container");

                if (fileContainer.length === 0) {
                    fileContainer = $("<div class='file-preview-container'></div>");
                    $(this).after(fileContainer);
                } else {
                    fileContainer.html(""); // Purani files ka preview clear karna
                }

                $.each(fileList, function(index, file) {
                    let filePreview = $(`
                <div class="file-preview" data-index="${index}">
                    <span>${file.name}</span>
                    <button type="button" class="btn btn-sm remove-file" data-index="${index}"><i class="fas fa-times"></i></button>
                </div>
            `);
                    fileContainer.append(filePreview);
                });

                // Delete functionality
                fileContainer.off("click").on("click", ".remove-file", function() {
                    let index = $(this).data("index");
                    let dt = new DataTransfer();

                    $.each(fileInput.files, function(i, file) {
                        if (i != index) {
                            dt.items.add(file);
                        }
                    });

                    fileInput.files = dt.files; // Update file input
                    $(this).parent().remove(); // Sirf selected file remove karega
                });
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            // Format on blur (when user leaves input)
            $('.decimal-input').on('blur', function() {
                let val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    $(this).val(val.toFixed(2));
                }
            });

            // Also format all inputs before form submission
            $('form').on('submit', function() {
                $('.decimal-input').each(function() {
                    let val = parseFloat($(this).val());
                    if (!isNaN(val)) {
                        $(this).val(val.toFixed(2));
                    }
                });
            });
        });
    </script>

</body>

</html>
