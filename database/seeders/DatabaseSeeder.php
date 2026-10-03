<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the database with admin, officer, citizen accounts and sample reports.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin account
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'barangay' => 'Centro 1 (Pob.)',
            'email_verified_at' => now(),
        ]);

        // Create officer accounts
        $officer1 = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'officer@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'officer',
            'barangay' => 'Centro 2 (Pob.)',
            'email_verified_at' => now(),
        ]);

        $officer2 = User::create([
            'name' => 'Maria Santos',
            'email' => 'officer2@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'officer',
            'barangay' => 'Macanaya',
            'email_verified_at' => now(),
        ]);

        // Create citizen accounts
        $citizen1 = User::create([
            'name' => 'Pedro Reyes',
            'email' => 'citizen@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'citizen',
            'barangay' => 'Bangag',
            'email_verified_at' => now(),
        ]);

        $citizen2 = User::create([
            'name' => 'Ana Garcia',
            'email' => 'citizen2@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'citizen',
            'barangay' => 'Linao',
            'email_verified_at' => now(),
        ]);

        $citizen3 = User::create([
            'name' => 'Roberto Cruz',
            'email' => 'citizen3@kalikasanwatch.ph',
            'password' => Hash::make('password'),
            'role' => 'citizen',
            'barangay' => 'Punta',
            'email_verified_at' => now(),
        ]);

        // Create sample reports
        $reports = [
            [
                'user_id' => $citizen1->id,
                'title' => 'Illegal dumping near Cagayan River bank',
                'description' => 'Large amounts of household waste and construction debris have been dumped near the river bank in Bangag. This is contaminating the water and posing health risks to nearby residents.',
                'category' => 'illegal_dumping',
                'severity' => 'high',
                'latitude' => 18.3612,
                'longitude' => 121.6289,
                'status' => 'investigating',
                'assigned_to' => $officer1->id,
            ],
            [
                'user_id' => $citizen2->id,
                'title' => 'Water pollution in irrigation canal',
                'description' => 'The irrigation canal in Linao has turned dark green with algae bloom. Residents report a foul smell and dead fish have been spotted.',
                'category' => 'water_pollution',
                'severity' => 'high',
                'latitude' => 18.3490,
                'longitude' => 121.6350,
                'status' => 'pending',
                'assigned_to' => null,
            ],
            [
                'user_id' => $citizen3->id,
                'title' => 'Unauthorized tree cutting in Punta',
                'description' => 'Several mature mangrove trees have been cut down along the coastline near Punta barangay. The area is part of the protected mangrove zone.',
                'category' => 'deforestation',
                'severity' => 'high',
                'latitude' => 18.3700,
                'longitude' => 121.6400,
                'status' => 'investigating',
                'assigned_to' => $officer2->id,
            ],
            [
                'user_id' => $citizen1->id,
                'title' => 'Noise pollution from construction site',
                'description' => 'A construction site near the market area has been operating machinery well past 10 PM. Residents are unable to sleep.',
                'category' => 'noise_pollution',
                'severity' => 'medium',
                'latitude' => 18.3555,
                'longitude' => 121.6310,
                'status' => 'resolved',
                'assigned_to' => $officer1->id,
            ],
            [
                'user_id' => $citizen2->id,
                'title' => 'Flooding in low-lying area of Macanaya',
                'description' => 'The drainage system in Macanaya is clogged with garbage, causing flooding even during light rain. Water levels reach knee-high.',
                'category' => 'flooding',
                'severity' => 'medium',
                'latitude' => 18.3620,
                'longitude' => 121.6250,
                'status' => 'pending',
                'assigned_to' => null,
            ],
            [
                'user_id' => $citizen3->id,
                'title' => 'Illegal fishing using dynamite',
                'description' => 'Reports of blast fishing near the Apparri coastline. Explosions were heard during early morning hours. Dead fish were found floating.',
                'category' => 'illegal_fishing',
                'severity' => 'high',
                'latitude' => 18.3800,
                'longitude' => 121.6500,
                'status' => 'pending',
                'assigned_to' => null,
            ],
            [
                'user_id' => $citizen1->id,
                'title' => 'Soil erosion along farm road',
                'description' => 'Heavy rains have caused significant soil erosion along the farm road connecting Bangag to the main highway.',
                'category' => 'soil_erosion',
                'severity' => 'low',
                'latitude' => 18.3530,
                'longitude' => 121.6270,
                'status' => 'resolved',
                'assigned_to' => $officer2->id,
            ],
            [
                'user_id' => $citizen2->id,
                'title' => 'Open burning of garbage in Centro',
                'description' => 'A household in Centro 5 regularly burns garbage including plastics in their backyard, causing respiratory issues.',
                'category' => 'air_pollution',
                'severity' => 'medium',
                'latitude' => 18.3580,
                'longitude' => 121.6330,
                'status' => 'rejected',
                'assigned_to' => $officer1->id,
            ],
        ];

        foreach ($reports as $reportData) {
            $report = Report::create($reportData);

            // Add sample comments to some reports
            if ($report->status !== 'pending') {
                Comment::create([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to ?? $officer1->id,
                    'message' => 'This report has been received and is being reviewed by our team.',
                ]);
            }
        }

        // Add citizen follow-up comment
        Comment::create([
            'report_id' => 1,
            'user_id' => $citizen1->id,
            'message' => 'I have additional photos if needed. The situation seems to be getting worse.',
        ]);

        Comment::create([
            'report_id' => 1,
            'user_id' => $officer1->id,
            'message' => 'Thank you for the report. We have dispatched a team to investigate.',
        ]);
    }
}
