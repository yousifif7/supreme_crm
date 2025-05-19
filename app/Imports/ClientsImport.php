<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClientsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Client([
            'client_name'      => $row['client_name'],
            'address'          => $row['address'],
            'contact_number'   => $row['contact_number'],
            'fax'              => $row['fax'],
            'email'            => $row['email'],
        ]);
    }
}
