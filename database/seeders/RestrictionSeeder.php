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
    Restriction::truncate();
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


$otherChecks = [
    ['max_weekly_hours_check', 'hour_per_week', 'The guard cannot be assigned more than 40 hours in a week.'],
    ['student_visa_hours_check', 'visa_type', 'The guard cannot be assigned more than 20 hours a week.'],
    ['min_rest_hours_check', 'min_break_hours', 'The guard had a shift within the latest 12 hours.'],
];


foreach ($otherChecks as $check) {
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
