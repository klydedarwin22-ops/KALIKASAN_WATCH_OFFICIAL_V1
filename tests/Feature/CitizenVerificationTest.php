<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_staff_can_review_and_approve_a_citizens_id(): void
    {
        Storage::fake('local');
        $idPath = 'barangay-ids/citizen.png';
        Storage::disk('local')->put(
            $idPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jP6sAAAAASUVORK5CYII=')
        );
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_id_path' => $idPath,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($citizen)
            ->get(route('verification.id', $citizen))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('verification.id', $citizen))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($admin)
            ->patch(route('verification.approve', $citizen), ['notes' => 'ID checked'])
            ->assertRedirect(route('verification.index'));

        $this->assertTrue($citizen->fresh()->barangay_verified);
        $this->assertSame($admin->id, $citizen->fresh()->verified_by);
    }

    public function test_unverified_citizens_do_not_see_the_id_upload_card_on_the_dashboard(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => false,
        ]);

        $this->actingAs($citizen)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Citizenship Verification')
            ->assertDontSee('name="barangay_id"')
            ->assertDontSee('Start camera');
    }

    public function test_approval_is_blocked_until_an_id_is_stored(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_id_path' => null,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('verification.approve', $citizen))
            ->assertNotFound();

        $this->assertFalse($citizen->fresh()->barangay_verified);
    }

    public function test_citizens_can_resubmit_an_id_after_rejection(): void
    {
        Storage::fake('local');
        $previousIdPath = 'barangay-ids/old-id.png';
        Storage::disk('local')->put(
            $previousIdPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jP6sAAAAASUVORK5CYII=')
        );
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_id_path' => $previousIdPath,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('verification.reject', $citizen), ['notes' => 'Please upload a clearer image.'])
            ->assertRedirect(route('verification.index'));

        $citizen->refresh();
        $this->assertNull($citizen->barangay_id_path);
        $this->assertSame('Please upload a clearer image.', $citizen->verification_notes);
        Storage::disk('local')->assertMissing($previousIdPath);

        $this->actingAs($citizen)
            ->post(route('verification.upload'), [
                'barangay_id' => UploadedFile::fake()->createWithContent(
                    'new-id.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jP6sAAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('dashboard'));

        $citizen->refresh();
        $this->assertNotSame($previousIdPath, $citizen->barangay_id_path);
        $this->assertNull($citizen->verification_notes);
        $this->assertFalse($citizen->barangay_verified);
        Storage::disk('local')->assertMissing($previousIdPath);
        Storage::disk('local')->assertExists($citizen->barangay_id_path);
    }
}
