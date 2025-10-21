<?php

use App\Http\Controllers\Docs\AdminSettingController;
use App\Http\Controllers\Docs\AuthController;
use App\Http\Controllers\Docs\DigitalFormController;
use App\Http\Controllers\Docs\DigitalFormSubmitController;
use App\Http\Controllers\Docs\DynamicInputsController;
use App\Http\Controllers\Docs\PageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Docs\ApplicationFormController;

Route::group(['middleware' => 'auth'], function () {

    // ========================= Admin Settings =========================
    Route::controller(AdminSettingController::class)->group(function () {
        Route::get('admin/setting', 'index')
            ->name('admin.setting.index')
            ->middleware('can:Read HR Managment');

        Route::post('admin/setting/store', 'create')
            ->name('admin.setting.create')
            ->middleware('can:Write HR Managment');
    });

    // ========================= Digital Form =========================
    Route::controller(DigitalFormController::class)->group(function () {
        Route::get('digital/form/index', 'index')
            ->name('digital.form.index')
            ->middleware('can:Read HR Managment');

        Route::get('digital/form/create', 'create')
            ->name('digital.form.create')
            ->middleware('can:Create HR Managment');

        Route::post('digital/form/store', 'store')
            ->name('digital.form.store')
            ->middleware('can:Create HR Managment');

        Route::get('digital/form/edit/{id}', 'edit')
            ->name('digital.form.edit')
            ->middleware('can:Write HR Managment');

        Route::post('digital/form/update', 'update')
            ->name('digital.form.update')
            ->middleware('can:Write HR Managment');

        Route::delete('digital/form/{id}', 'destroy')
            ->name('digital.form.destroy')
            ->middleware('can:Delete HR Managment');

        Route::post('digital/form/update-order', 'updateOrder')
            ->name('digital.form.order')
            ->middleware('can:Write HR Managment');

        Route::post('paginate/update-status', 'updateStatus')
            ->name('paginate.update.status')
            ->middleware('can:Write HR Managment');
    });

    // ========================= Dynamic Inputs =========================
    Route::controller(DynamicInputsController::class)->group(function () {
        Route::get('digital/form/field/{id}', 'index')
            ->name('digital.form.fields')
            ->middleware('can:Read HR Managment');

        Route::get('digital/form/field/create/{id}', 'create')
            ->name('digital.form.fields.create')
            ->middleware('can:Create HR Managment');

        Route::post('digital/form/field/store', 'store')
            ->name('digital.form.fields.store')
            ->middleware('can:Create HR Managment');

        Route::get('digital/form/field/edit/{id}', 'edit')
            ->name('digital.form.field.edit')
            ->middleware('can:Write HR Managment');

        Route::post('digital/form/field/update', 'update')
            ->name('digital.form.field.update')
            ->middleware('can:Write HR Managment');

        Route::delete('digital/form/field/{id}', 'destroy')
            ->name('digital.form.field.destroy')
            ->middleware('can:Delete HR Managment');

        Route::post('digital/form/field/update-order', 'updateOrderfield')
            ->name('digital.form.field.order')
            ->middleware('can:Write HR Managment');

        // Child fields
        Route::get('digital/form/field/child/{id}', 'childindex')
            ->name('digital.form.fields.child')
            ->middleware('can:Read HR Managment');

        Route::get('digital/form/field/create/child/{id}', 'childcreate')
            ->name('digital.form.fields.create.child')
            ->middleware('can:Create HR Managment');

        Route::post('digital/form/field/child/store', 'childstore')
            ->name('digital.form.fields.store.child')
            ->middleware('can:Create HR Managment');

        Route::get('digital/form/field/child/edit/{id}', 'childedit')
            ->name('digital.form.field.edit.child')
            ->middleware('can:Write HR Managment');

        Route::post('digital/form/field/child/update', 'childupdate')
            ->name('digital.form.field.update.child')
            ->middleware('can:Write HR Managment');

        Route::delete('digital/form/field/child/{id}', 'childdestroy')
            ->name('digital.form.field.destroy.child')
            ->middleware('can:Delete HR Managment');

        Route::post('digital/form/field/child/update-order', 'updateOrderfieldchild')
            ->name('digital.form.field.child.order')
            ->middleware('can:Write HR Managment');

        Route::post('/update-status', 'updateStatus')
            ->name('update.status')
            ->middleware('can:Write HR Managment');
    });

    // ========================= Pages =========================
    Route::controller(PageController::class)->group(function () {
        Route::get('page/form', 'index')
            ->name('page.form.index')
            ->middleware('can:Read HR Managment');

        Route::get('page/form/create', 'create')
            ->name('page.form.create')
            ->middleware('can:Create HR Managment');

        Route::post('page/form/store', 'store')
            ->name('page.form.store')
            ->middleware('can:Create HR Managment');

        Route::get('page/form/edit/{id}', 'edit')
            ->name('page.form.edit')
            ->middleware('can:Write HR Managment');

        Route::post('page/form/update', 'update')
            ->name('page.form.update')
            ->middleware('can:Write HR Managment');

        Route::delete('page/form/{id}', 'destroy')
            ->name('page.form.destroy')
            ->middleware('can:Delete HR Managment');
    });

    // ========================= Digital Form Submissions =========================
    Route::controller(DigitalFormSubmitController::class)->group(function () {
        Route::get('form/shows', 'index')
            ->name('clientdetail.index')
            ->middleware('can:Read HR Managment');

        Route::get('form/client/detail/{id}', 'form_detail')
            ->name('form_detail.index')
            ->middleware('can:Read HR Managment');

        Route::get('form/client/detail/edit/{id}', 'form_detail_edit')
            ->name('form_detail.edit')
            ->middleware('can:Write HR Managment');

        Route::post('form/client/detail/update/{id}', 'form_detail_update')
            ->name('form_detail.update')
            ->middleware('can:Write HR Managment');

        Route::get('client/complete/{id}', 'client_detail')
            ->name('client.complete.detail.index')
            ->middleware('can:Read HR Managment');

        Route::post('client/complete/{id}', 'update_form')
            ->name('client.complete.detail.update')
            ->middleware('can:Write HR Managment');

        Route::delete('client/detail/delete/{id}', 'destroy')
            ->name('clientdetail.destroy')
            ->middleware('can:Delete HR Managment');

        Route::post('/delete-file', 'deleteFile')
            ->name('delete.file')
            ->middleware('can:Delete HR Managment');

        Route::post('/mail/send', 'mail_send')
            ->name('mail.send')
            ->middleware('can:Export HR Managment');
    });

    // ========================= Application Form =========================
    Route::controller(ApplicationFormController::class)->group(function () {
        Route::get('application/form/show', 'index')
            ->name('application.form.show')
            ->middleware('can:Read HR Managment');

        Route::get('application/form/edit/{id}', 'edit')
            ->name('application.form.edit')
            ->middleware('can:Write HR Managment');
    });

    Route::controller(PageController::class)->group(function () {
        Route::get('User/form/incident/data/view/{id}', 'incident_data_view')
            ->name('application.form.incident.data.view')
            ->middleware('can:Read HR Managment');
    });
});

// ========================= Public Form Routes =========================
Route::controller(PageController::class)->group(function () {
    Route::get('/form/incident', 'incident')->name('application.form.incident');
    Route::post('/form/incident/submit', 'incident_form_submit')->name('application.form.incident.submit');
    Route::get('User/form/incident/data', 'incident_data')->name('application.form.incident.data');
});

Route::controller(PageController::class)->group(function () {
    Route::get('form/{id}', 'form_design')->name('page.form.design');
    Route::post('form/submit', 'form_design_submit')->name('digital.form.submit');
});

Route::controller(ApplicationFormController::class)->group(function () {
    Route::get('application/form/create', 'create')->name('application.form.create');
    Route::post('application/form/store', 'store')->name('application.form.store');
});

?>
