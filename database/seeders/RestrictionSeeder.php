<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    use App\Models\Restriction;

class RestrictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

public function run()
{
    $expiryChecks = [
        ['expiry_check', 'sia_expiry', 'Staff SIA license has expired.'],
        ['expiry_check', 'visa_expiry', 'Staff Visa has expired.'],
        ['expiry_check', 'passport_expiry', 'Staff Passport has expired.'],
    ];

    foreach ($expiryChecks as $check) {
        Restriction::updateOrCreate(
            [
                'entity_type'      => \App\Models\Employee::class,
                'restriction_type' => $check[0],
                'field_name'       => $check[1],
            ],
            [
                'error_message'    => $check[2],
                'is_active'        => true,
            ]
        );
    }

    // Document checks (as discussed previously)
    $documents = [
        ['document_check', 'sia_licence_file', 'SIA Licence File is missing.'],
        ['document_check', 'passport_file', 'Passport File is missing.'],
        ['document_check', 'proof_of_address_file', 'Proof of Address File is missing.'],
        ['document_check', 'ni_letter_file', 'NI Letter File is missing.'],
        ['document_check', 'first_aid_certificate_file', 'First Aid Certificate File is missing.'],
        ['document_check', 'act_certificate_file', 'ACT Certificate File is missing.'],
    ];

    foreach ($documents as $doc) {
        Restriction::updateOrCreate(
            [
                'entity_type'      => \App\Models\Employee::class,
                'restriction_type' => $doc[0],
                'field_name'       => $doc[1],
            ],
            [
                'error_message'    => $doc[2],
                'is_active'        => true,
            ]
        );
    }
}

}
