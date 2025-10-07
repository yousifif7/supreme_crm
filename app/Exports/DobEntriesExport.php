<?php 
namespace App\Exports;

use App\Models\User;
use App\Models\DobEntry;
use App\Models\ShiftDate;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DobEntriesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DobEntry::get()->map(function($dob) {
            $user = User::find($dob->user_id);
            $shiftdate= ShiftDate::find($dob->shift_id);
            return [
                'ID' => $dob->id,
                'User' => $user ? $user->first_name . ' ' . $user->last_name : 'Unknown',
                'Title' => $dob->title,
                'Type' => $dob->entry_type,
                'Timestamp' => $dob->timestamp,
                'address' => $shiftdate? $shiftdate->shift->site->address : 'Unknown',
                'Location' => json_encode($dob->location),
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'User', 'Title', 'Type', 'Timestamp', 'Address' ,'Location'];
    }
}
