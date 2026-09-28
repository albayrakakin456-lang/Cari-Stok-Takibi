<?php

namespace Tests\Feature;

use Tests\TestCase;

class IntegrationDocumentationTest extends TestCase
{
    public function test_public_integration_documentation_is_available(): void
    {
        $this->get('/integration/docs')
            ->assertOk()
            ->assertSee('API v1 & Webhook Dokümantasyonu', false)
            ->assertSee('invoice.created')
            ->assertSee('X-Webhook-Signature')
            ->assertSee('Postman Collection');
    }
}
