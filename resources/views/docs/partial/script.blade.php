<!-- JAVASCRIPT -->
<script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>

<!-- owl.carousel js -->
<script src="{{ asset('assets/libs/owl.carousel/owl.carousel.min.js') }}"></script>

<!-- auth-2-carousel init -->
<script src="{{ asset('assets/js/pages/auth-2-carousel.init.js') }}"></script>

<!-- App js -->
<script src="{{ asset('assets/js/app.js') }}"></script>

<script src="//cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script>
    let table = new DataTable('#myTable');

</script>
<script src="{{asset('login/assets/plugins/sweetalert/sweetalert2.all.min.js')}}"></script>
<script src="{{asset('login/assets/plugins/sweetalert/sweetalerts.min.js')}}"></script>
<script type="text/javascript">
$(document).ready(function() {
    $(document).on("click", ".confirm-text", function(event) { 
        event.preventDefault(); 
        var form = $(this).closest("form");

        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-danger"
            },
            buttonsStyling: false
        });

        swalWithBootstrapButtons.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel!",
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
                swalWithBootstrapButtons.fire({
                    title: "Deleted!",
                    text: "Your record has been deleted.",
                    icon: "success"
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                swalWithBootstrapButtons.fire({
                    title: "Cancelled",
                    text: "Your record is safe",
                    icon: "error"
                });
            }
        });
    });
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
    };

    @if(Session::has('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(Session::has('message'))
        toastr.success("{{ session('message') }}");
    @endif

    @if(Session::has('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if(Session::has('info'))
        toastr.info("{{ session('info') }}");
    @endif

    @if(Session::has('warning'))
        toastr.warning("{{ session('warning') }}");
    @endif

    @if($errors->any())
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif
</script>

  <script>
$('#select-all').on('click', function() {
    var isChecked = $(this).prop('checked');
    $('.select-option').prop('checked', isChecked);
});

  </script>

<script>
    $(document).ready(function () {
        $(".dropdown-menu .active").each(function () {
            $(this).closest(".nav-item").find("a.nav-link").addClass("active");
        });
    });
</script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
<script>
    
$(document).ready(function() {
    $('#summernote').summernote({
        tabsize: 2,
        height: 200
      });
});
</script>

<script>
    
$(document).ready(function() {
    $('#summernote1').summernote({
        tabsize: 2,
        height: 200
      });
});
</script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        const nameInput = document.querySelector(".name-input");
        const slugInput = document.querySelector(".slug-input");
    
        nameInput.addEventListener("keyup", function () {
            let slug = nameInput.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "");
            slugInput.value = slug;
        });
    });
    </script>

<!-- Include Flatpickr CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


<script>
$(document).ready(function() {
    $(document).on('change', '.status-toggle', function() { // Event delegation
        let status = $(this).is(':checked') ? 1 : 0;
        let id = $(this).data('id');

        $.ajax({
            url: "{{ route('update.status') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("Status updated successfully!");
                } else {
                    toastr.error("Error updating status.");
                }
            },
            error: function() {
                alert("Something went wrong.");
            }
        });
    });
});


$(document).ready(function() {
    $(document).on('change', '.status-toggle-paginate', function() { // Event delegation
        let status = $(this).is(':checked') ? 1 : 0;
        let id = $(this).data('id');

        $.ajax({
            url: "{{ route('paginate.update.status') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("Status updated successfully!");
                } else {
                    toastr.error("Error updating status.");
                }
            },
            error: function() {
                alert("Something went wrong.");
            }
        });
    });
});
</script>
@yield('script')