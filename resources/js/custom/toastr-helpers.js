window.toast_danger = function (message)
{
    toastr.error(message, {
        positionClass: "toast-top-right",
        progressBar: true
    });
};

window.toast_success = function (message)
{
    toastr.success(message, {
        positionClass: "toast-top-right",
        progressBar: true
    });
};

window.toast_undo = function (url)
{
  toastr.clear();
  var message = `<div class="mb-3">Removed Successfully</div>
    <button type="button" data-href="${url}" data-call-restore class="btn btn-primary btn-sm me-2">Undo</button>`;

    toastr.success(message, {
        positionClass: "toast-top-right",
        closeButton: true,
        progressBar: true,
        timeOut: 30000,
    });
}
