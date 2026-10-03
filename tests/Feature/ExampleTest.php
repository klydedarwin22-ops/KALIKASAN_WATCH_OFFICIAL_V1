<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_report_creation_form_includes_location_fields(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports/create');

        $response
            ->assertOk()
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertDontSee('name="severity"', false)
            ->assertSee('Use my current location')
            ->assertSee('navigator.geolocation')
            ->assertDontSee('Assign to Officer');
    }

    public function test_citizen_report_creation_is_unassigned_and_severity_is_automatic(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $response = $this
            ->actingAs($citizen)
            ->post(route('reports.store'), [
                'title' => 'Test unassigned report',
                'description' => 'This report should be available for officer assignment.',
                'category' => 'illegal_dumping',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
            ]);

        $response->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('reports', [
            'user_id' => $citizen->id,
            'assigned_to' => null,
            'status' => 'investigating',
            'severity' => 'medium',
        ]);
    }

    public function test_report_severity_is_estimated_without_gemini_for_uploaded_image(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();

        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->post(route('reports.store'), [
                'title' => 'Flooded roadside',
                'description' => 'Water is covering the roadside.',
                'category' => 'flooding',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'image' => UploadedFile::fake()->createWithContent(
                    'flooding.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC')
                ),
            ])
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('reports', [
            'user_id' => $citizen->id,
            'title' => 'Flooded roadside',
            'severity' => 'high',
        ]);

        Http::assertNothingSent();
    }

    public function test_local_report_severity_estimator_returns_low_medium_and_high(): void
    {
        $estimator = app(\App\Services\ReportSeverityEstimator::class);

        $this->assertSame('low', $estimator->estimate('illegal_dumping', 'A small, contained pile of waste.'));
        $this->assertSame('medium', $estimator->estimate('other', 'Several blocked drains are causing persistent water.'));
        $this->assertSame('high', $estimator->estimate('other', 'A toxic chemical spill is causing immediate danger.'));
    }

    public function test_impact_severity_options_are_ordered_high_to_low(): void
    {
        $this->assertSame(['high', 'medium', 'low'], \App\Models\Report::SEVERITIES);
    }

    public function test_officer_dashboard_shows_recent_assigned_reports(): void
    {
        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Recently assigned report',
            'description' => 'This should show in the officer recent assigned section.',
            'category' => 'illegal_dumping',
            'severity' => 'high',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
        ]);

        $response = $this
            ->actingAs($officer)
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Recently Assigned')
            ->assertSee('Recently assigned report')
            ->assertSee('Investigating');
    }

    public function test_officer_dashboard_shows_all_assigned_reports(): void
    {
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        for ($index = 1; $index <= 6; $index++) {
            \App\Models\Report::create([
                'user_id' => $citizen->id,
                'title' => "Assigned report {$index}",
                'description' => 'Assigned report visibility test.',
                'category' => 'illegal_dumping',
                'severity' => 'medium',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'status' => 'investigating',
                'assigned_to' => $officer->id,
            ]);
        }

        $this->actingAs($officer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Assigned report 1')
            ->assertSee('Assigned report 6');
    }

    public function test_officer_dashboard_orders_recent_reports_by_impact_severity_high_to_low(): void
    {
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        foreach ([
            ['title' => 'Dashboard high severity', 'severity' => 'high', 'created_at' => now()->subDays(3)],
            ['title' => 'Dashboard medium severity', 'severity' => 'medium', 'created_at' => now()->subDays(2)],
            ['title' => 'Dashboard low severity', 'severity' => 'low', 'created_at' => now()->subDay()],
        ] as $data) {
            \App\Models\Report::create($data + [
                'user_id' => $citizen->id,
                'description' => 'Dashboard severity ordering test report.',
                'category' => 'other',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'status' => 'investigating',
            ]);
        }

        $this->actingAs($officer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard high severity',
                'Dashboard medium severity',
                'Dashboard low severity',
            ]);
    }

    public function test_admin_dashboard_shows_officer_accountability_counts_and_actions(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
            'phone' => '+639171234567',
        ]);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        foreach ([
            ['title' => 'Overdue accountability report', 'status' => 'investigating', 'is_completed' => false, 'estimated_due_at' => now()->subDay()],
            ['title' => 'Completed accountability report', 'status' => 'resolved', 'is_completed' => true, 'estimated_due_at' => now()->subDays(2)],
            ['title' => 'Active accountability report', 'status' => 'investigating', 'is_completed' => false, 'estimated_due_at' => now()->addDay()],
        ] as $data) {
            \App\Models\Report::create($data + [
                'user_id' => $citizen->id,
                'title' => $data['title'],
                'description' => 'Officer accountability dashboard test.',
                'category' => 'other',
                'severity' => 'medium',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'assigned_to' => $officer->id,
                'assigned_at' => now()->subDays(3),
            ]);
        }

        $summary = app(\App\Services\DashboardService::class)
            ->getOfficerAccountability()
            ->first(fn ($entry) => $entry->officer->is($officer));

        $this->assertSame(3, $summary->assigned_count);
        $this->assertSame(1, $summary->completed_count);
        $this->assertSame(2, $summary->in_progress_count);
        $this->assertSame(1, $summary->overdue_count);

        config(['services.twilio.supervisor_phone' => '+639171111111']);
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Officer Accountability')
            ->assertSee($officer->name)
            ->assertSee('1 report require attention')
            ->assertSee('Overdue accountability report')
            ->assertSee(route('admin.officers.history', $officer), false)
            ->assertSee(route('reports.index', ['assigned_to' => $officer->id]), false)
            ->assertSee('Escalate to Supervisor');
    }

    public function test_admin_can_view_assignment_history_and_send_officer_notifications(): void
    {
        config([
            'services.twilio.sid' => 'test-account-sid',
            'services.twilio.token' => 'test-auth-token',
            'services.twilio.from' => '+15005550006',
            'services.twilio.supervisor_phone' => '+639171111111',
        ]);
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
            'phone' => '+639171234567',
        ]);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Officer history report',
            'description' => 'A test assignment history record.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('reports.assign', $report), ['assigned_to' => $officer->id]);

        $this->actingAs($admin)
            ->get(route('admin.officers.history', $officer))
            ->assertOk()
            ->assertSee('Assignment History')
            ->assertSee('Officer history report');

        $this->actingAs($admin)
            ->post(route('admin.officers.notify', $officer))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.officers.escalate', $officer))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        Http::assertSentCount(3);
        Http::assertSent(fn (HttpRequest $request) => $request['To'] === $officer->phone);
        Http::assertSent(fn (HttpRequest $request) => $request['To'] === '+639171111111');
    }

    public function test_officers_can_view_all_citizen_reports(): void
    {
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $otherOfficer = \App\Models\User::factory()->create(['role' => 'officer']);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        foreach ([
            ['title' => 'My assigned report', 'assigned_to' => $officer->id],
            ['title' => 'Other officer report', 'assigned_to' => $otherOfficer->id],
            ['title' => 'Unassigned citizen report', 'assigned_to' => null],
        ] as $reportData) {
            \App\Models\Report::create([
                'user_id' => $citizen->id,
                'title' => $reportData['title'],
                'description' => 'Officer visibility test report.',
                'category' => 'illegal_dumping',
                'severity' => 'medium',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'status' => 'investigating',
                'assigned_to' => $reportData['assigned_to'],
            ]);
        }

        $this->actingAs($officer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My assigned report')
            ->assertSee('Other officer report')
            ->assertSee('Unassigned citizen report');

        $this->actingAs($officer)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Assigned Officer')
            ->assertSee($officer->name)
            ->assertSee('My assigned report')
            ->assertSee('Other officer report')
            ->assertSee('Unassigned citizen report');

        $otherOfficerReport = \App\Models\Report::where('title', 'Other officer report')->firstOrFail();

        $this->actingAs($officer)
            ->get(route('reports.show', $otherOfficerReport))
            ->assertOk();
    }

    public function test_officer_can_view_but_cannot_update_another_officers_report(): void
    {
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $otherOfficer = \App\Models\User::factory()->create(['role' => 'officer']);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Private assigned report',
            'description' => 'This report belongs to another officer.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $otherOfficer->id,
        ]);

        $this->actingAs($officer)
            ->get(route('reports.show', $report))
            ->assertOk();

        $this->actingAs($officer)
            ->get(route('reports.edit', $report))
            ->assertForbidden();

        $this->actingAs($officer)
            ->patch(route('reports.update-status', $report), ['status' => 'resolved'])
            ->assertForbidden();

        $this->actingAs($officer)
            ->patch(route('reports.assign', $report), ['assigned_to' => $officer->id])
            ->assertForbidden();
    }

    public function test_citizen_can_see_resolution_details_on_their_report(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Resolved report',
            'description' => 'Citizen should see the resolution timestamp here.',
            'category' => 'illegal_dumping',
            'severity' => 'high',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'resolved',
            'assigned_to' => $officer->id,
        ]);

        $response = $this
            ->actingAs($citizen)
            ->get(route('reports.show', $report));

        $response
            ->assertOk()
            ->assertSee('Report status: Resolved')
            ->assertSee('Impact Severity')
            ->assertSee('High')
            ->assertSee('Resolution Time')
            ->assertSee('Resolved');
    }

    public function test_reports_index_shows_resolution_time_for_resolved_reports(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Resolved report in list',
            'description' => 'This resolved report should show a time in the report list.',
            'category' => 'illegal_dumping',
            'severity' => 'high',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'resolved',
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($citizen)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertSee('Resolution Time')
            ->assertSee('Report Status')
            ->assertSee('Impact Severity')
            ->assertSee($report->title);
    }

    public function test_reports_index_orders_rows_by_impact_severity_high_to_low(): void
    {
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);

        foreach ([
            ['title' => 'High severity report', 'severity' => 'high', 'created_at' => now()->subDays(3)],
            ['title' => 'Medium severity report', 'severity' => 'medium', 'created_at' => now()->subDays(2)],
            ['title' => 'Low severity report', 'severity' => 'low', 'created_at' => now()->subDay()],
        ] as $data) {
            \App\Models\Report::create($data + [
                'user_id' => $citizen->id,
                'description' => 'Severity ordering test report.',
                'category' => 'other',
                'latitude' => 18.1234,
                'longitude' => 121.5678,
                'status' => 'investigating',
            ]);
        }

        $this->actingAs($citizen)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'High severity report',
                'Medium severity report',
                'Low severity report',
            ]);
    }

    public function test_officer_can_record_estimated_days_to_finish(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Surveyed report',
            'description' => 'Officer should be able to estimate the completion days.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
            'assigned_to' => $officer->id,
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'investigating',
                'estimated_days_to_finish' => 4,
            ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'estimated_days_to_finish' => 4,
            'status' => 'investigating',
        ]);
    }

    public function test_officer_can_set_estimate_and_see_actions_for_unassigned_report(): void
    {
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Unassigned officer action report',
            'description' => 'Officer can begin processing this report.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => null,
        ]);

        $this->actingAs($officer)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Estimated Days to Finish (1–5 days)')
            ->assertSee('Rejection Reason (required when rejecting)')
            ->assertDontSee('Assign Officer');

        $this->actingAs($officer)
            ->patch(route('reports.update-status', $report), [
                'status' => 'investigating',
                'estimated_days_to_finish' => 3,
            ])
            ->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => null,
            'estimated_days_to_finish' => 3,
        ]);
    }

    public function test_officer_cannot_set_estimated_days_above_five(): void
    {
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Days limit report',
            'description' => 'Estimate must not exceed five days.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
        ]);

        $this->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'investigating',
                'estimated_days_to_finish' => 6,
            ])
            ->assertSessionHasErrors('estimated_days_to_finish');
    }

    public function test_rejection_requires_a_reason_and_shows_it_to_the_citizen(): void
    {
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $officer = \App\Models\User::factory()->create(['role' => 'officer']);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Rejected report',
            'description' => 'Report with a rejection reason.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
        ]);

        $this->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), ['status' => 'rejected'])
            ->assertSessionHasErrors('rejection_reason');

        $reason = 'Duplicate report; the same issue is already under investigation.';
        $this->actingAs($officer)
            ->patch(route('reports.update-status', $report), [
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ])
            ->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->actingAs($citizen)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Reason for rejection')
            ->assertSee($reason);
    }

    public function test_only_admin_can_reassign_and_new_assignee_can_reject(): void
    {
        config([
            'services.twilio.sid' => 'test-account-sid',
            'services.twilio.token' => 'test-auth-token',
            'services.twilio.from' => '+15005550006',
        ]);
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $currentOfficer = \App\Models\User::factory()->create(['role' => 'officer']);
        $newOfficer = \App\Models\User::factory()->create([
            'role' => 'officer',
            'phone' => '+639171234567',
        ]);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Reassignment report',
            'description' => 'Officer transfers this report to another officer.',
            'category' => 'other',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
            'assigned_to' => $currentOfficer->id,
        ]);

        $this->actingAs($currentOfficer)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Rejection Reason (required when rejecting)')
            ->assertSee('value="1"', false)
            ->assertSee('value="5"', false)
            ->assertDontSee('Reassign Officer');

        $this->actingAs($currentOfficer)
            ->patch(route('reports.assign', $report), ['assigned_to' => $newOfficer->id])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Reassign Officer')
            ->assertSee($newOfficer->phone);

        $this->actingAs($admin)
            ->patch(route('reports.assign', $report), ['assigned_to' => $newOfficer->id])
            ->assertRedirect(route('reports.show', $report))
            ->assertSessionHas('success', 'Officer assigned and SMS notification sent.');

        Http::assertSent(fn (HttpRequest $request) =>
            str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === $newOfficer->phone
            && str_contains($request['Body'], 'Reassignment report')
        );

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => $newOfficer->id,
            'status' => 'investigating',
        ]);

        $this->actingAs($newOfficer)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('value="rejected"', false)
            ->assertSee('Rejection Reason (required when rejecting)');

        $this->actingAs($newOfficer)
            ->patch(route('reports.update-status', $report), [
                'status' => 'rejected',
                'rejection_reason' => 'This officer reviewed the transferred report and provided a valid reason.',
            ])
            ->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => $newOfficer->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($currentOfficer)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertDontSee('Officer Actions')
            ->assertDontSee('Rejection Reason (required when rejecting)');

        $this->actingAs($newOfficer)
            ->put(route('reports.update', $report), ['status' => 'rejected'])
            ->assertSessionHasErrors('status');

        $this->actingAs($currentOfficer)
            ->patch(route('reports.update-status', $report), [
                'status' => 'rejected',
                'rejection_reason' => 'The former assignee must not be able to reject this report.',
            ])
            ->assertForbidden();
    }


    public function test_assignment_succeeds_with_warning_when_twilio_is_not_configured(): void
    {
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.from' => null,
        ]);
        Http::preventStrayRequests();

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
            'phone' => '+639171234567',
        ]);
        $citizen = \App\Models\User::factory()->create(['role' => 'citizen']);
        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'SMS configuration report',
            'description' => 'Assignment persists if SMS is unavailable.',
            'category' => 'other',
            'severity' => 'low',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('reports.assign', $report), ['assigned_to' => $officer->id])
            ->assertRedirect(route('reports.show', $report))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => $officer->id,
            'status' => 'investigating',
        ]);
        Http::assertNothingSent();
    }

    public function test_officer_can_mark_report_as_complete_when_finished(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Completed report',
            'description' => 'Officer should be able to mark it as complete.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
            'estimated_days_to_finish' => 3,
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'resolved',
                'is_completed' => '1',
                'completion_proof' => \Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'estimated_days_to_finish' => 3,
            'is_completed' => true,
        ]);
    }

    public function test_citizen_and_admin_can_see_completion_details(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
        ]);

        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Completion visibility test',
            'description' => 'Citizens and admins should see completion details.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'resolved',
            'estimated_days_to_finish' => 4,
            'is_completed' => true,
        ]);

        $adminResponse = $this
            ->actingAs($admin)
            ->get(route('reports.show', $report));

        $adminResponse
            ->assertOk()
            ->assertSee('Estimated Days to Finish')
            ->assertSee('Completion Status');

        $citizenResponse = $this
            ->actingAs($citizen)
            ->get(route('reports.show', $report));

        $citizenResponse
            ->assertOk()
            ->assertSee('Estimated Days to Finish')
            ->assertSee('Completion Status');
    }

    public function test_officer_must_upload_proof_photo_when_marking_report_complete(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Proof photo required',
            'description' => 'Officer must attach proof once task is finished.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'resolved',
                'is_completed' => '1',
            ]);

        $response->assertSessionHasErrors(['completion_proof']);
    }

    public function test_officer_can_remove_wrong_completion_proof(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Wrong proof removal test',
            'description' => 'Officer should be able to remove the wrong proof image.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'resolved',
            'assigned_to' => $officer->id,
            'is_completed' => true,
            'completion_proof' => 'completion_proofs/wrong.jpg',
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'resolved',
                'is_completed' => '1',
                'remove_completion_proof' => '1',
            ]);

        $response->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'completion_proof' => null,
            'is_completed' => true,
        ]);
    }

    public function test_officer_cannot_upload_multiple_completion_proof_photos(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Single proof only',
            'description' => 'Only one proof image should be allowed.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'investigating',
            'assigned_to' => $officer->id,
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update-status', $report), [
                'status' => 'resolved',
                'is_completed' => '1',
                'completion_proof' => [
                    \Illuminate\Http\UploadedFile::fake()->create('proof1.jpg', 100, 'image/jpeg'),
                    \Illuminate\Http\UploadedFile::fake()->create('proof2.jpg', 100, 'image/jpeg'),
                ],
            ]);

        $response->assertSessionHasErrors(['completion_proof']);
    }

    public function test_admin_can_comment_on_reports(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
        ]);

        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Admin comment test',
            'description' => 'Admin should be able to comment on a report.',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('reports.show', $report))
            ->post(route('reports.comment', $report), [
                'message' => 'Admin note for the report.',
            ]);

        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseHas('comments', [
            'report_id' => $report->id,
            'user_id' => $admin->id,
            'message' => 'Admin note for the report.',
        ]);
    }

    public function test_officers_cannot_comment_on_reports(): void
    {
        $citizen = \App\Models\User::factory()->create([
            'role' => 'citizen',
        ]);

        $officer = \App\Models\User::factory()->create([
            'role' => 'officer',
        ]);

        $report = \App\Models\Report::create([
            'user_id' => $citizen->id,
            'title' => 'Test report',
            'description' => 'Test description',
            'category' => 'illegal_dumping',
            'severity' => 'medium',
            'latitude' => 18.1234,
            'longitude' => 121.5678,
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($officer)
            ->from(route('reports.show', $report))
            ->post(route('reports.comment', $report), [
                'message' => 'This should be rejected.',
            ]);

        $response->assertForbidden();
    }
}
