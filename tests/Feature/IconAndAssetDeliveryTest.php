<?php

namespace Tests\Feature;

use Tests\TestCase;

class IconAndAssetDeliveryTest extends TestCase
{
    public function test_self_hosted_fontawesome_assets_exist(): void
    {
        $cssPath = public_path('vendor/fontawesome/css/all.min.css');
        $this->assertFileExists($cssPath);
        $this->assertGreaterThan(50000, filesize($cssPath));

        $solidFontPath = public_path('vendor/fontawesome/webfonts/fa-solid-900.woff2');
        $this->assertFileExists($solidFontPath);
        $this->assertGreaterThan(50000, filesize($solidFontPath));

        $brandsFontPath = public_path('vendor/fontawesome/webfonts/fa-brands-400.woff2');
        $this->assertFileExists($brandsFontPath);
        $this->assertGreaterThan(50000, filesize($brandsFontPath));

        $regularFontPath = public_path('vendor/fontawesome/webfonts/fa-regular-400.woff2');
        $this->assertFileExists($regularFontPath);
        $this->assertGreaterThan(20000, filesize($regularFontPath));
    }

    public function test_application_layout_renders_fontawesome_and_favicon(): void
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verifies Font Awesome stylesheets are present
        $this->assertStringContainsString('vendor/fontawesome/css/all.min.css', $content);
        $this->assertStringContainsString('cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', $content);

        // Verifies Favicon link is present
        $this->assertStringContainsString('favicon.png', $content);
    }

    public function test_htaccess_enforces_cors_headers_for_webfonts(): void
    {
        $htaccessPath = public_path('.htaccess');
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString('Header set Access-Control-Allow-Origin "*"', $content);
        $this->assertStringContainsString('woff2', $content);
    }
}
