<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\InvoicesDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;

class ClientInvoicesController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth','role:client']);
    }

    public function index(InvoicesDataTable $dataTable)
    {
        // Render the invoices DataTable scoped to the authenticated client
        $dataTable = $dataTable->withClient(Auth::id());
        return $dataTable->render('clients.invoices');
    }
}
