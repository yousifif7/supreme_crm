<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;

class ClientsExport implements FromCollection
{

    public function collection()
    {
        return Client::select('client_name', 'address', 'contact_number', 'contact_person', 'email')->get();
    }
}
