<!DOCTYPE html>
@section('title') {{$digitalform->title}} @endsection

@include('docs.partial.style')

<style>
.form-navigation{
    margin-bottom: 30px;
}
.open-picker{
    border-right: 0!important;
    border-left: 0!important;
    border-top: 0!important;    
}
input{
    border-right: 0!important;
    border-left: 0!important;
    border-top: 0!important;
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
    .title{
         color: rgb(32, 33, 36);
    }
    .page-indicator {
        color: #666;
        font-size: 14px;
}
.form-section p{
    font-weight: 400;
    font-size: 16px;
}
</style>

<body>
<form action="" method="post" enctype="multipart/form-data" id="dynamicformsubmit">
    @csrf
    <input type="hidden" value="{{$data->id}}" name="page_id">
    <input type="hidden" value="{{$digitalform->id}}" name="form_id">

    <div class="form-container">
        <div class="header">
            <img src="{{ asset('backend/websitedata/' . get_setting('dashboard_logo')) }}" alt="Logo" class="logo">
        </div>
        <div class="yellow-divider"></div>

        <div class="form-section">
            <h1 class="title fw-bold">{{ $data->title }}</h1>
            <p>{!! $data->desc !!}</p>
        </div>

        @foreach($dynamicinput as $input)
           <div class="form-section"  @if($input->type === 'heading') style="padding:0" @endif>
                        @if($input->type === 'heading')
                        <div class="card-header" @if($input->type === 'heading') style="background-color: rgb(223, 199, 0);
    padding: 13px 18px 4px 8px" @endif>
                                                        <h3 @if($input->type === 'heading') style="font-weight: 600;text-decoration: underline;font-size:15px" @endif>{{ $input->title }}</h3>
                        </div>
                            @if($input->desc)
                            <p style="padding:15px">{!!$input->desc!!}</p>
                            @endif
                        @elseif($input->label_status == 1)
                                <label class="form-title @if($input->required == 1) required @endif" 
           @if(strtolower($input->type) === 'paragraph') style="display:block;" @endif>
        <p>{!! $input->title !!}</p>
    </label>
                        @endif

            @if(in_array($input->type, ['text', 'email', 'number', 'date', 'password', 'tel', 'url']))
            <input type="{{$input->type}}" class="form-control" name="name[{{$input->id}}]" placeholder="{{ $input->placeholder }}" value="{{ $decodedData[$input->id] ?? '' }}">
            @elseif($input->type === 'textarea')
            <textarea class="form-control" name="name[{{$input->id}}]" placeholder="{{ $input->placeholder }}">{{ $decodedData[$input->id] ?? '' }}</textarea>
            @elseif($input->type === 'drop')
            <select class="form-select" name="name[{{$input->id}}]">
                <option value="">{{ $input->title }}</option>
                @foreach($input->child as $option)
                <option value="{{ $option->id }}" @if(isset($decodedData[$input->id]) && $decodedData[$input->id] == $option->id) selected @endif>{{ $option->title }}</option>
                @endforeach
            </select>
            @elseif($input->type === 'radio' && !empty($input->child))
            @foreach($input->child as $option)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="name[{{$input->id}}]" value="{{ $option->title }}" @if(isset($decodedData[$input->id]) && $decodedData[$input->id] == $option->title) checked @endif>
                <label class="form-check-label">{{ $option->title }}</label>
            </div>
            @endforeach
            @elseif($input->type === 'checkbox')
            @foreach($input->child as $child)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="name[{{$input->id}}][]" value="{{ $child->title }}" @if(isset($decodedData[$input->id]) && in_array($child->title, $decodedData[$input->id])) checked @endif>
                <label class="form-check-label">{{ $child->title }}</label>
            </div>
            @endforeach
            @elseif($input->type === 'file')
            <input type="file" class="form-control mt-2" name="name[{{$input->id}}]" multiple>
            @elseif($input->type === 'time')
            <input type="text" id="datetimepicker{{$input->id}}" class="form-control datetimepicker" placeholder="Select time" name="name[{{$input->id}}]" value="{{ $decodedData[$input->id] ?? '' }}">
            @endif
        </div>
        @endforeach

        @if($digitalform->desc)
            <div class="form-section">
                {!! $digitalform->desc !!}
            </div>
        @endif

        <div class="form-navigation text-center">
            @can('client detail edit')
            <button type="submit" id="submitForm" class="btn btn-success">Submit</button>
            @endcan
        </div>
    </div>
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
                                if (inputField.next('.invalid-feedback').length === 0) {
                                    inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
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