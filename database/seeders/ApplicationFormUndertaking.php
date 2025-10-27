<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationFormUndertaking extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query="INSERT INTO `apllication_form_undertakings` (`id`, `application_id`, `full_name`, `job_title`, `date`, `date_sign`, `created_at`, `updated_at`) VALUES
(1, '1', 'Taylor Daniels', 'Adipisicing aliquid', '27-Jan-1997', '23-Mar-1972', '2025-08-05 16:06:44', '2025-08-05 16:06:44'),
(2, '2', 'Andrew Coleman', 'Proident et consect', '13-Nov-1995', '26-Jul-2023', '2025-08-15 05:02:08', '2025-08-15 05:02:08'),
(3, '3', 'Myra Crawford', 'Laudantium velit ma', '27-Jan-2022', '03-Feb-2010', '2025-08-15 22:15:45', '2025-08-15 22:15:45'),
(4, '4', 'Jasmine Mann', 'In omnis fuga Irure', '16-May-1988', '25-Aug-2020', '2025-08-15 22:17:26', '2025-08-15 22:17:26')";

        DB::insert($query);
    }
}
