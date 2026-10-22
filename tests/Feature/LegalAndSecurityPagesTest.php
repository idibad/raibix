<?php

namespace Tests\Feature;

use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class LegalAndSecurityPagesTest extends TestCase
{
    public function test_privacy_policy_page_renders_successfully(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Central/PrivacyPolicy')
        );

        // Test alias route
        $aliasResponse = $this->get('/privacy-policy');
        $aliasResponse->assertStatus(200);
        $aliasResponse->assertInertia(fn (Assert $page) => $page
            ->component('Central/PrivacyPolicy')
        );
    }

    public function test_terms_of_service_page_renders_successfully(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Central/TermsOfService')
        );

        // Test alias route
        $aliasResponse = $this->get('/terms-of-service');
        $aliasResponse->assertStatus(200);
        $aliasResponse->assertInertia(fn (Assert $page) => $page
            ->component('Central/TermsOfService')
        );
    }

    public function test_security_and_privacy_page_renders_successfully(): void
    {
        $response = $this->get('/security');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Central/SecurityPrivacy')
        );

        // Test alias route
        $aliasResponse = $this->get('/security-privacy');
        $aliasResponse->assertStatus(200);
        $aliasResponse->assertInertia(fn (Assert $page) => $page
            ->component('Central/SecurityPrivacy')
        );
    }
}
