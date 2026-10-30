<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantLogoAutoCropTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_logo_with_large_transparent_margins_is_auto_cropped(): void
    {
        $id = 'testcrop' . uniqid();
        $domain = $id . '.localhost';

        $tenant = Tenant::create([
            'id' => $id,
            'name' => 'Al-Huda Academy',
            'status' => 'active',
        ]);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        tenancy()->initialize($tenant);

        // Create a 200x200 transparent image with a 40x40 solid content block centered inside
        $tempPath = tempnam(sys_get_temp_dir(), 'test_logo') . '.png';
        $im = imagecreatetruecolor(200, 200);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);

        // Draw solid red square from (80, 80) to (120, 120)
        $red = imagecolorallocatealpha($im, 220, 38, 38, 0);
        imagefilledrectangle($im, 80, 80, 120, 120, $red);
        imagepng($im, $tempPath);
        imagedestroy($im);

        $uploadedFile = new UploadedFile($tempPath, 'padded_logo.png', 'image/png', null, true);

        $admin = User::create([
            'name' => 'Principal',
            'email' => 'admin@alhuda.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post("http://{$domain}/admin/settings/logo", [
            'logo' => $uploadedFile,
            'logo_bg' => 'dark',
        ]);

        $response->assertRedirect();
        $tenant->refresh();

        $savedLogoRelative = $tenant->logo;
        $this->assertNotNull($savedLogoRelative);
        $savedLogoFull = public_path(ltrim($savedLogoRelative, '/'));
        $this->assertFileExists($savedLogoFull);

        // Check cropped dimensions: should be around 40-70px, NOT 200px!
        $info = getimagesize($savedLogoFull);
        $this->assertLessThan(100, $info[0], 'Logo width was not trimmed of transparent margins');
        $this->assertLessThan(100, $info[1], 'Logo height was not trimmed of transparent margins');

        // Cleanup
        if (file_exists($tempPath)) @unlink($tempPath);
        if (file_exists($savedLogoFull)) @unlink($savedLogoFull);

        tenancy()->end();
    }
}
