const ajaxAllowedResponseMethods = [
    // 'test'
];
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    async: true,
    beforeSend: function (jqXHR, settings) {
        // Get current datetime in ISO format
        const now = new Date();
        const datetime = now.toISOString(); // e.g., "2021-03-19T23:25:59.120Z"

        // Add datetime stamp to request headers
        jqXHR.setRequestHeader('X-Datetime', datetime);
    }
});

function handlePostAjaxSuccess(response) {
    if (response.data?.JsMethods != undefined && response.data?.JsMethods != null && response.data?.JsMethods != '') {
        for (var i = 0; i < response.data.JsMethods.length; i++) {
            // check if method name is in the ajaxAllowedResponseMethods array
            if (/^[a-zA-Z_.]+$/.test(response.data.JsMethods[i]) && ajaxAllowedResponseMethods.includes(response.data.JsMethods[i])) {
                if (response.data.JsMethodsParams != undefined && response.data.JsMethodsParams != null && response.data.JsMethodsParams != '') {
                    // call the function whose name is in the response.data.function with all the params
                    typeof window[response.data.JsMethods[i]] == "function" ? window[response.data.JsMethods[i]](response.data.JsMethodsParams[i]) : null;
                } else {
                    eval(response.data.JsMethods[i] + '()');
                }
            } else {
                console.log('Method not allowed' + response.data.JsMethods[i]);
            }
        }
    }

    if (response.data?.event == 'table_reload') {
        if (response.data.table_id != undefined && response.data.table_id != null && response.data.table_id != '') {
            $('#' + response.data.table_id).DataTable().ajax.reload(null, false);
        } else {
            $('#dataTableBuilder').DataTable().ajax.reload(null, false);
        }
    }
    if (response.data?.event == 'page_reload') {
        setTimeout(function () { // wait for 1 second
            location.reload(); // then reload the page
        }, 1000);
    }
    if (response.data?.event == 'redirect') {
        setTimeout(function () { // wait for 1 second
            window.location.href = response.data.url;
        }, 1000);
    }
    if (response.data?.event == 'functionCall') {
        // call the function whose name is in the response.data.function
        if (typeof response.data.function_params != "undefined" && response.data.function_params != null && response.data.function_params != '')
            typeof window[response.data.function] == "function" ? window[response.data.function](response.data.function_params) : null;
        else
            typeof window[response.data.function] == "function" ? window[response.data.function]() : null;
    }

    if (response.data.close == 'globalModal') {
        $('#globalModal').modal('hide');
    } else if (response.data.close == 'modal') {
        current.closest('.modal').modal('hide');
    }
}

$('.close_modal').on('click', function () {
    $(this).closest('.modal').modal('hide');
});

// ajax modal
$(document).on('click', '[data-toggle="ajax-modal"]', function () {
    var title = $(this).data('title');
    var url = $(this).data('href');
    console.log(url);
    var modal_size = $(this).data('size');
    var modal_width = $(this).data('width');
    if (typeof modal_width != 'undefined' && modal_width != '') {
        $('#globalModal').find('.modal-dialog').css('min-width', modal_width);
    } else {
        $('#globalModal').find('.modal-dialog').css('min-width', '');
    }
    $('#globalModal').find('.modal-dialog').removeClass('modal-lg modal-sm modal-xs modal-xl');
    if (typeof modal_size == 'undefined' || modal_size == '') {
        modal_size = 'modal-lg';
    }
    $('#globalModal').find('.modal-dialog').addClass(modal_size);
    $('#globalModalTitle').html(title);
    $.ajax({
        type: 'get',
        url: url,
        success: function (response) {
            // Check if modaltitle exists in the response and is not empty
            var modalTitle = response.data && response.data.modaltitle ? response.data.modaltitle.trim() : null;

            if (modalTitle) {
                $('#globalModal .modal-header').html(modalTitle);
            } else {
                $('#globalModalTitle').html(title);
                // $('#globalModalTitle').siblings().remove();
            }
            // extract modal footer div from view_data
            const modalFooter = response.data && response.data.view_data ? $(response.data.view_data).find('.modal-footer').prop('outerHTML') : null;
            // remove modal footer from global modal
            $('#globalModal .modal-footer').remove();
            if (modalFooter) {
                // add modal footer to global modal
                $('#globalModalBody').after(modalFooter);
            }
            // remove modal footer from view_data
            const clonedViewData = $(response.data.view_data).clone();
            clonedViewData.find('.modal-footer').remove();
            $('#globalModalBody').html(clonedViewData);
            //   initModalSelect2();
            //   if(typeof initFlatPickr != 'undefined'){
            //     initFlatPickr();
            //   }
            handlePostAjaxSuccess(response);
            $('#globalModal').modal('show');
        }
    });
});

function update_global_modal(params) {
    params = JSON.parse(params);
    $('#globalModalBody').html(params.view_data);
}

window.reloadDatatable = function (tableId) {
    if (typeof tableId === 'undefined' || tableId === null || tableId === '') {
        tableId = '#dataTableBuilder';
    }
    $(tableId).DataTable().ajax.reload(null, false);
};

window.openBsModal = function (modalId) {
    if (typeof modalId === 'undefined' || modalId === null || modalId === '') {
        modalId = '#globalModal';
    }
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId.replace('#', '')));
    modal.show();
}

window.closeBsModal = function (modalId) {
    if (typeof modalId === 'undefined' || modalId === null || modalId === '') {
        modalId = '#globalModal';
    }
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId.replace('#', '')));
    modal.hide();
}
