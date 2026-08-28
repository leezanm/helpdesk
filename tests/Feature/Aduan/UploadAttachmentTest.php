<?php

namespace Tests\Feature\Aduan;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "upload attachment" path.
 *
 * ReportAttachmentController@store stores an uploaded file and returns its
 * path/name/type as JSON (consumed today by the dropzone/AJAX uploader).
 * Uses a fake disk so no real files are written. Requirement 1 (task 1.2).
 */
class UploadAttachmentTest extends FeatureTestCase
{
    public function test_authenticated_user_can_upload_an_attachment()
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('bukti.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)->post('/aduan/attachments/store', [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['path', 'name', 'type']);
        $response->assertJson(['name' => 'bukti.pdf']);

        Storage::disk('local')->assertExists('public/aduan/attachments/'.$file->hashName());
    }

    public function test_guest_cannot_upload_an_attachment()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('bukti.pdf', 120, 'application/pdf');

        $response = $this->post('/aduan/attachments/store', ['file' => $file]);

        $response->assertRedirect('/login');
    }
}
