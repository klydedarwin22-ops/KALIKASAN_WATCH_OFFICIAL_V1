<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_login_for_enrolled_verified_citizens_waits_for_face_verification(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'face_template' => array_fill(0, 128, 0.25),
        ]);

        $this->post(route('login'), [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect(route('login.face'));

        $this->assertGuest();
        $this->assertSame($citizen->id, session('pending_face_user_id'));
    }

    public function test_enrolled_citizens_are_authenticated_only_after_a_successful_face_match(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'face_template' => array_fill(0, 128, 0.25),
        ]);

        $this->post(route('login'), [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect(route('login.face'));

        $this->get(route('login.face'))->assertOk()->assertSee('Verify it’s you');

        $service = \Mockery::mock(FaceRecognitionService::class);
        $service->shouldReceive('analyze')->once()->andReturn([]);
        $service->shouldReceive('matches')->once()->andReturn(true);
        $this->app->instance(FaceRecognitionService::class, $service);

        $this->post(route('login.face.verify'), [
            'frames' => $this->cameraFrames(),
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($citizen);
        $this->assertNull(session('pending_face_user_id'));
    }

    public function test_a_failed_face_match_keeps_the_citizen_unauthenticated(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'face_template' => array_fill(0, 128, 0.25),
        ]);

        $this->post(route('login'), [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect(route('login.face'));
        $this->get(route('login.face'))->assertOk();

        $service = \Mockery::mock(FaceRecognitionService::class);
        $service->shouldReceive('analyze')->once()->andReturn([]);
        $service->shouldReceive('matches')->once()->andReturn(false);
        $this->app->instance(FaceRecognitionService::class, $service);

        $this->post(route('login.face.verify'), [
            'frames' => $this->cameraFrames(),
        ])->assertSessionHasErrors('frames');

        $this->assertGuest();
        $this->assertSame($citizen->id, session('pending_face_user_id'));
    }

    public function test_verified_citizens_must_enroll_before_opening_the_dashboard(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'barangay_id_path' => 'barangay-ids/citizen.png',
        ]);

        $this->actingAs($citizen)
            ->get(route('dashboard'))
            ->assertRedirect(route('face.enroll'));

        $this->get(route('face.enroll'))
            ->assertOk()
            ->assertSee('I consent to storing an encrypted face template');
    }

    public function test_face_enrollment_requires_consent_and_stores_an_encrypted_template(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
        ]);
        $template = array_fill(0, 128, 0.25);

        $this->actingAs($citizen)->get(route('face.enroll'))->assertOk();

        $service = \Mockery::mock(FaceRecognitionService::class);
        $service->shouldReceive('analyze')->once()->andReturn([]);
        $service->shouldReceive('validatedTemplate')->once()->andReturn($template);
        $this->app->instance(FaceRecognitionService::class, $service);

        $this->post(route('face.enroll.store'), [
            'consent' => '1',
            'frames' => $this->cameraFrames(),
        ])->assertRedirect(route('dashboard'));

        $this->assertSame($template, $citizen->fresh()->face_template);
        $this->assertNotSame(json_encode($template), $citizen->fresh()->getRawOriginal('face_template'));
    }

    public function test_enrollment_cannot_be_submitted_without_explicit_consent(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
        ]);

        $this->actingAs($citizen)->get(route('face.enroll'))->assertOk();

        $this->post(route('face.enroll.store'), [
            'frames' => $this->cameraFrames(),
        ])->assertSessionHasErrors('consent');

        $this->assertNull($citizen->fresh()->face_template);
    }

    public function test_revoking_verification_also_removes_the_encrypted_face_template(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'face_template' => array_fill(0, 128, 0.25),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('verification.revoke', $citizen))
            ->assertRedirect(route('verification.show', $citizen));

        $citizen->refresh();
        $this->assertFalse($citizen->barangay_verified);
        $this->assertNull($citizen->face_template);
    }

    public function test_admin_can_reset_a_citizens_face_enrollment(): void
    {
        Storage::fake('local');
        $idPath = 'barangay-ids/citizen.png';
        Storage::disk('local')->put($idPath, 'private ID');
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'barangay_verified' => true,
            'barangay_id_path' => $idPath,
            'face_template' => array_fill(0, 128, 0.25),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('verification.show', $citizen))
            ->assertOk()
            ->assertSee('Reset face login');

        $this->actingAs($admin)
            ->post(route('admin.citizens.face.reset', $citizen))
            ->assertRedirect(route('verification.show', $citizen));

        $this->assertNull($citizen->fresh()->face_template);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function cameraFrames(): array
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jP6sAAAAASUVORK5CYII=');

        return array_map(
            fn (int $index) => UploadedFile::fake()->createWithContent("frame-{$index}.png", $png),
            range(1, 4),
        );
    }
}
