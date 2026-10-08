<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_officers_without_a_status_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeOfficer = User::factory()->create([
            'role' => 'officer',
            'name' => 'Active Officer',
            'is_active' => true,
        ]);
        $inactiveOfficer = User::factory()->create([
            'role' => 'officer',
            'name' => 'Inactive Officer',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($activeOfficer->name)
            ->assertSee($inactiveOfficer->name)
            ->assertDontSee('Officer status')
            ->assertDontSee('All statuses')
            ->assertDontSee('Filter');
    }

    public function test_officer_dashboard_does_not_show_the_removed_account_status_panel(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $this->actingAs($officer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Your account status')
            ->assertDontSee('You can change your availability status here.')
            ->assertDontSee('Mark Active')
            ->assertDontSee('Mark Inactive');

        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('officer.status'));
    }

    public function test_accountability_section_shows_officers_online_or_offline(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $onlineOfficer = User::factory()->create([
            'role' => 'officer',
            'name' => 'Recently Seen Officer',
            'is_online' => true,
        ]);
        $offlineOfficer = User::factory()->create([
            'role' => 'officer',
            'name' => 'Long Ago Officer',
            'is_online' => false,
        ]);
        $neverSeenOfficer = User::factory()->create([
            'role' => 'officer',
            'name' => 'Never Seen Officer',
            'is_online' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Online Status')
            ->assertSee('Recently Seen Officer')
            ->assertSee('Long Ago Officer')
            ->assertSee('Never Seen Officer')
            ->assertSee('Online')
            ->assertSee('Offline');
        $this->assertTrue($onlineOfficer->fresh()->is_online);
        $this->assertFalse($offlineOfficer->fresh()->is_online);
        $this->assertFalse($neverSeenOfficer->fresh()->is_online);
    }

    public function test_accountability_table_omits_account_status_and_keeps_online_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officer = User::factory()->create([
            'role' => 'officer',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($officer->name)
            ->assertSee('Online Status')
            ->assertDontSee('Account Status')
            ->assertDontSee('Mark Active');

        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('officer.status'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('admin.officers.status'));
    }

}
