<?php

namespace App\Exports\Reports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;


class ClientReportExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $managerId = $this->request->input('manager_id');
        $contractStart = $this->request->input('contract_start');
        $contractEnd = $this->request->input('contract_end');
        $isActive = $this->request->input('is_active');

        $clients = Client::with(['company', 'manager'])
            ->when($managerId, fn($q) => $q->where('manager_id', $managerId))
            ->when($contractStart, fn($q) => $q->whereDate('contract_start', '>=', $contractStart))
            ->when($contractEnd, fn($q) => $q->whereDate('contract_end', '<=', $contractEnd))
            ->when($isActive !== null && $isActive !== '', fn($q) => $q->where('is_active', $isActive))
            ->get();

        return $clients->map(function ($client) {
            return [
                'Client Name' => $client->client_name,
                'Company' => $client->company->name ?? 'N/A',
                'Manager' => $client->manager ? $client->manager->fore_name . ' ' . $client->manager->sur_name : 'N/A',
                'Contact Person' => $client->contact_person ?? 'N/A',
                'Email' => $client->email ?? 'N/A',
                'Contact Number' => $client->contact_number ?? 'N/A',
                'Contract Start' => $client->contract_start ? $client->contract_start->format('d/m/Y') : 'N/A',
                'Contract End' => $client->contract_end ? $client->contract_end->format('d/m/Y') : 'N/A',
                'Status' => $client->is_active ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Client Name',
            'Company',
            'Manager',
            'Contact Person',
            'Email',
            'Contact Number',
            'Contract Start',
            'Contract End',
            'Status',
        ];
    }
}

