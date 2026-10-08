<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapTest extends TestCase
{
    use RefreshDatabase;

    public function test_environmental_map_omits_rejected_reports(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        Report::create([
            'user_id' => $citizen->id,
            'title' => 'Visible pending report',
            'description' => 'This report should appear on the map.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
        ]);

        Report::create([
            'user_id' => $citizen->id,
            'title' => 'Hidden rejected report',
            'description' => 'Rejected reports should not appear on the map.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.2345,
            'longitude' => 121.6789,
            'status' => 'rejected',
        ]);

        $this->get(route('map.index'))
            ->assertOk()
            ->assertSee('Visible pending report')
            ->assertDontSee('Hidden rejected report')
            ->assertDontSee('Rejected')
            ->assertSee('1 reports plotted');
    }
}
