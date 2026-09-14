<?php

namespace App\Http\Controllers {
    // Only loaded by the explicit audit suite. Freeze invoice time for a deterministic collision test.
    function time(): int { return $GLOBALS['audit_invoice_time'] ?? \time(); }
}

namespace Tests\Audit {

use App\Models\{User, Contact, Product, Sale, Purchase, CashTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\{Auth, DB, Queue, Bus, RateLimiter};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuditCsrfMiddleware extends VerifyCsrfToken
{
    protected function runningUnitTests() { return false; }
}

class ExtendedAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('SQLite :memory: is required.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['audit_invoice_time']);
        parent::tearDown();
    }

    private function fixtures(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $customer = Contact::create(['name' => 'Audit Customer', 'type' => 'customer']);
        $supplier = Contact::create(['name' => 'Audit Supplier', 'type' => 'supplier']);
        $product = Product::create($this->productPayload());
        return [$user, $customer, $supplier, $product];
    }

    private function productPayload(): array
    {
        return ['name' => 'Audit Product', 'code' => 'AUDIT-EXT', 'purchase_price' => 10, 'sale_price' => 20, 'stock' => 10, 'min_stock' => 2, 'tax_rate' => 20];
    }

    private function invoicePayload(Contact $contact, Product $product): array
    {
        return ['contact_id' => $contact->id, 'product_id' => [$product->id], 'quantity' => [2], 'unit_price' => [20]];
    }

    public static function protectedWrites(): array
    {
        $cases = [];
        foreach (['contacts', 'products', 'sales', 'purchases', 'cash'] as $resource) {
            $cases[] = ['POST', '/'.$resource];
        }
        $cases[] = ['DELETE', '/sales/1'];
        $cases[] = ['DELETE', '/purchases/1'];
        return $cases;
    }

    #[DataProvider('protectedWrites')]
    public function test_guest_cannot_write(string $method, string $path): void
    {
        $this->call($method, $path)->assertRedirect('/login');
        foreach (['contacts', 'products', 'sales', 'purchases', 'cash_transactions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public static function invoiceKinds(): array { return [['sales'], ['purchases']]; }

    #[DataProvider('invoiceKinds')]
    public function test_foreign_invoice_cannot_be_cancelled(string $kind): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $this->post('/'.$kind, $this->invoicePayload($kind === 'sales' ? $customer : $supplier, $product))->assertSessionHasNoErrors();
        $model = $kind === 'sales' ? Sale::class : Purchase::class;
        $id = $model::firstOrFail()->id;
        $stock = $product->fresh()->stock;
        $cashCount = CashTransaction::count();
        $this->actingAs(User::factory()->create());
        $response = $this->from('/'.$kind)->delete('/'.$kind.'/'.$id);
        $this->assertLessThan(500, $response->status());
        $this->assertDatabaseHas($kind, ['id' => $id]);
        $this->assertEquals($stock, DB::table('products')->where('id', $product->id)->value('stock'));
        $this->assertEquals($cashCount, DB::table('cash_transactions')->count());
    }

    public function test_foreign_cash_contact_and_mass_assignment_are_rejected(): void
    {
        [$owner, $customer] = $this->fixtures();
        $attacker = User::factory()->create();
        $this->actingAs($attacker);
        $this->post('/cash', ['contact_id' => $customer->id, 'type' => 'out', 'amount' => 10, 'description' => 'Audit'])->assertSessionHasErrors('contact_id');
        $this->post('/contacts', ['name' => 'Injected', 'type' => 'customer', 'user_id' => $owner->id, 'balance' => 9999])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contacts', ['name' => 'Injected', 'user_id' => $attacker->id, 'balance' => 0]);
    }

    #[DataProvider('invoiceKinds')]
    public function test_invoice_rejects_wrong_contact_type(string $kind): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $wrong = $kind === 'sales' ? $supplier : $customer;
        $this->post('/'.$kind, $this->invoicePayload($wrong, $product))->assertSessionHasErrors('contact_id');
        $this->assertDatabaseCount($kind, 0);
    }

    public static function malformedFields(): array
    {
        return [
            'sale negative quantity' => ['sales', 'quantity', [-1]],
            'sale zero quantity' => ['sales', 'quantity', [0]],
            'sale fractional quantity' => ['sales', 'quantity', [1.5]],
            'sale negative price' => ['sales', 'unit_price', [-1]],
            'sale textual price' => ['sales', 'unit_price', ['abc']],
            'sale missing product' => ['sales', 'product_id', [999999]],
            'sale scalar product' => ['sales', 'product_id', 1],
            'sale array contact' => ['sales', 'contact_id', [1]],
            'purchase negative quantity' => ['purchases', 'quantity', [-1]],
            'purchase array contact' => ['purchases', 'contact_id', [2]],
            'purchase nested product' => ['purchases', 'product_id', [[1]]],
            'purchase invalid paid switch' => ['purchases', 'is_paid', 'not-boolean'],
            'cash zero amount' => ['cash', 'amount', 0],
            'cash negative amount' => ['cash', 'amount', -1],
            'cash invalid type' => ['cash', 'type', 'bogus'],
            'cash array contact' => ['cash', 'contact_id', [1]],
            'cash decimal overflow' => ['cash', 'amount', 100000000],
            'product negative stock' => ['products', 'stock', -1],
            'product negative price' => ['products', 'sale_price', -1],
            'product excessive tax' => ['products', 'tax_rate', 101],
            'product fractional stock' => ['products', 'stock', 0.5],
            'product integer overflow' => ['products', 'stock', 2147483648],
            'product decimal overflow' => ['products', 'purchase_price', 100000000],
        ];
    }

    #[DataProvider('malformedFields')]
    public function test_malformed_fields_are_validation_errors(string $kind, string $field, mixed $value): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $payload = match ($kind) {
            'sales' => $this->invoicePayload($customer, $product),
            'purchases' => $this->invoicePayload($supplier, $product),
            'cash' => ['type' => 'out', 'amount' => 10, 'description' => 'Audit expense', 'contact_id' => $customer->id],
            'products' => array_replace($this->productPayload(), ['code' => 'NEW-AUDIT']),
        };
        $payload[$field] = $value;
        $response = $this->from('/'.$kind.'/create')->post('/'.$kind, $payload);
        $this->assertLessThan(500, $response->status(), $kind.'.'.$field.' caused HTTP '.$response->status());
        $response->assertSessionHasErrors();
    }

    public static function badPasswords(): array
    {
        return ['array' => [[1,2,3,4,5,6]], 'null-byte' => ["abcdef\0ghijk"]];
    }

    #[DataProvider('badPasswords')]
    public function test_malformed_registration_password_is_rejected(mixed $password): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Audit', 'email' => 'audit@example.test', 'password' => $password, 'password_confirmation' => $password,
        ]);
        $this->assertLessThan(500, $response->status(), 'Malformed registration password returned '.$response->status());
        $response->assertSessionHasErrors('password');
    }

    public function test_array_login_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $response = $this->post('/login', ['email' => $user->email, 'password' => ['invalid']]);
        $this->assertLessThan(500, $response->status(), 'Array login password returned '.$response->status());
        $response->assertSessionHasErrors();
    }

    public function test_password_hash_string_cannot_be_used_as_plaintext_password(): void
    {
        $submitted = password_hash('known-audit-password', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->post('/register', ['name' => 'Audit', 'email' => 'hash-input@example.test', 'password' => $submitted, 'password_confirmation' => $submitted])->assertSessionHasNoErrors();
        $stored = User::where('email', 'hash-input@example.test')->firstOrFail()->password;
        $this->assertTrue(password_verify($submitted, $stored), 'A hash-looking submitted password was stored verbatim, so the submitted password will not log in.');
    }

    public function test_throttled_registration_does_not_flash_password(): void
    {
        RateLimiter::hit('register|127.0.0.1', 60);
        for ($i = 0; $i < 4; $i++) { RateLimiter::hit('register|127.0.0.1', 60); }
        $this->post('/register', ['name' => 'Audit', 'email' => 'audit@example.test', 'password' => 'SensitiveAudit123', 'password_confirmation' => 'SensitiveAudit123'])->assertSessionHasErrors();
        $this->assertNull(session('_old_input.password'), 'Rate-limit response retained the plaintext password in session.');
        $this->assertNull(session('_old_input.password_confirmation'));
    }

    public function test_csrf_is_enforced_for_writes(): void
    {
        $this->app->bind(VerifyCsrfToken::class, AuditCsrfMiddleware::class);
        $this->post('/register', [])->assertStatus(419);
        $this->withSession(['_token' => 'audit-csrf-token'])->post('/register', ['_token' => 'wrong-token'])->assertStatus(419);
        $this->withSession(['_token' => 'audit-csrf-token'])->post('/register', ['_token' => 'audit-csrf-token'])->assertRedirect()->assertSessionHasErrors();
    }

    public function test_stored_html_is_escaped_in_contact_views(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = '<script>alert("audit")</script>';
        $this->post('/contacts', ['name' => $payload, 'type' => 'customer', 'note' => $payload])->assertSessionHasNoErrors();
        $id = Contact::firstOrFail()->id;
        foreach (['/contacts', '/contacts/'.$id] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee($payload, false)->assertSee(e($payload), false);
        }
    }

    #[DataProvider('invoiceKinds')]
    public function test_two_invoices_in_same_second_can_be_created(string $kind): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $GLOBALS['audit_invoice_time'] = 1900000000;
        $payload = $this->invoicePayload($kind === 'sales' ? $customer : $supplier, $product);
        $this->post('/'.$kind, $payload)->assertSessionHasNoErrors();
        $this->post('/'.$kind, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount($kind, 2);
    }

    #[DataProvider('invoiceKinds')]
    public function test_database_failure_rolls_back_invoice_and_stock(string $kind): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        DB::unprepared("CREATE TRIGGER audit_reject_stock BEFORE INSERT ON stock_movements BEGIN SELECT RAISE(ABORT, 'audit injected failure'); END");
        $this->post('/'.$kind, $this->invoicePayload($kind === 'sales' ? $customer : $supplier, $product))->assertSessionHasErrors();
        $this->assertDatabaseCount($kind, 0);
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_product_and_opening_stock_are_atomic(): void
    {
        $this->actingAs(User::factory()->create());
        DB::unprepared("CREATE TRIGGER audit_reject_stock BEFORE INSERT ON stock_movements BEGIN SELECT RAISE(ABORT, 'audit injected failure'); END");
        $response = $this->post('/products', $this->productPayload());
        $this->assertDatabaseCount('products', 0, 'Product remained saved after opening stock failed. HTTP '.$response->status());
    }

    public function test_queue_failure_does_not_report_a_committed_sale_as_failed(): void
    {
        [, $customer, , $product] = $this->fixtures();
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('audit unavailable queue'));
        $response = $this->post('/sales', $this->invoicePayload($customer, $product));
        $this->assertDatabaseCount('sales', 1);
        $response->assertSessionHasNoErrors();
    }

    public function test_repeat_sale_cancellation_does_not_refund_twice(): void
    {
        [, $customer, , $product] = $this->fixtures();
        $this->post('/sales', $this->invoicePayload($customer, $product))->assertSessionHasNoErrors();
        $id = Sale::firstOrFail()->id;
        $this->delete('/sales/'.$id)->assertSessionHasNoErrors();
        $count = CashTransaction::count();
        $this->from('/sales')->delete('/sales/'.$id)->assertSessionHasErrors();
        $this->assertEquals($count, CashTransaction::count());
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_credit_purchase_and_partial_payment_balance(): void
    {
        [, , $supplier, $product] = $this->fixtures();
        $this->post('/purchases', $this->invoicePayload($supplier, $product))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->post('/cash', ['contact_id' => $supplier->id, 'type' => 'out', 'amount' => 15, 'description' => 'Partial payment'])->assertSessionHasNoErrors();
        $this->get('/contacts/'.$supplier->id)->assertOk()->assertViewHas('balance', 25);
    }

    public function test_customer_overpayment_is_not_labelled_zero_balance(): void
    {
        [, $customer] = $this->fixtures();
        $this->post('/cash', ['contact_id' => $customer->id, 'type' => 'in', 'amount' => 15, 'description' => 'Advance'])->assertSessionHasNoErrors();
        $this->withSession(['locale' => 'en'])->get('/contacts/'.$customer->id)->assertOk()->assertViewHas('balance', -15)->assertDontSee('Account settled / Zero balance');
    }

    public static function formKinds(): array { return [['sales'], ['purchases']]; }

    #[DataProvider('formKinds')]
    public function test_validation_error_preserves_invoice_rows(string $kind): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $payload = $this->invoicePayload($kind === 'sales' ? $customer : $supplier, $product);
        $payload['quantity'] = [0];
        $payload['unit_price'] = [123.45];
        $this->from('/'.$kind.'/create')->post('/'.$kind, $payload)->assertSessionHasErrors();
        $html = $this->get('/'.$kind.'/create')->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xp = new \DOMXPath($dom);
        $selected = $xp->query('//select[@name="product_id[]"]/option[@selected]');
        $this->assertGreaterThan(0, $selected->length, 'Submitted invoice rows vanished after validation.');
        $price = $xp->query('//input[@name="unit_price[]"]')->item(0);
        $this->assertEquals('123.45', $price->getAttribute('value'));
    }

    public function test_daily_cash_uses_istanbul_calendar_day(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(\Carbon\Carbon::parse('2026-09-14 22:30:00', 'UTC'));
        $tx = CashTransaction::create(['type' => 'in', 'amount' => 15, 'description' => 'Earlier Istanbul day']);
        $tx->forceFill(['created_at' => '2026-09-14 20:00:00'])->save();
        // Now is Sep 15 01:30 in Istanbul; the transaction was Sep 14 23:00, so not today.
        $response = $this->get('/')->assertOk();
        $this->travelBack();
        $response->assertViewHas('todayCashIn', 0);
    }

    public function test_turkish_initial_displays_valid_character(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Çağrı Audit']));
        $response = $this->get('/')->assertOk();
        $this->assertStringNotContainsString("\u{FFFD}", $response->getContent(), 'Navbar initial contains a replacement character for Turkish name.');
    }

    public function test_every_post_form_has_csrf_token(): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $this->post('/sales', $this->invoicePayload($customer, $product))->assertSessionHasNoErrors();
        $this->post('/purchases', $this->invoicePayload($supplier, $product))->assertSessionHasNoErrors();
        foreach (['/contacts/create', '/products/create', '/sales/create', '/purchases/create', '/cash/create', '/sales/'.Sale::first()->id, '/purchases/'.Purchase::first()->id] as $path) {
            $dom = new \DOMDocument();
            @$dom->loadHTML($this->get($path)->assertOk()->getContent());
            $xp = new \DOMXPath($dom);
            foreach ($xp->query('//form') as $form) {
                if (strtolower($form->getAttribute('method')) === 'post') {
                    $this->assertSame(1, $xp->query('.//input[@name="_token"]', $form)->length, $path.' has missing CSRF input');
                }
            }
        }
    }

    public function test_render_inline_javascript_for_syntax_audit(): void
    {
        [, $customer, $supplier, $product] = $this->fixtures();
        $this->post('/sales', $this->invoicePayload($customer, $product))->assertSessionHasNoErrors();
        $this->post('/purchases', $this->invoicePayload($supplier, $product))->assertSessionHasNoErrors();
        $scripts = [];
        foreach (['tr','en'] as $locale) {
            $this->withSession(['locale' => $locale]);
            foreach (['/sales/create', '/purchases/create', '/sales/'.Sale::first()->id, '/purchases/'.Purchase::first()->id] as $path) {
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">'.$this->get($path)->assertOk()->getContent());
                $xp = new \DOMXPath($dom);
                foreach ($xp->query('//script[not(@src)]') as $script) {
                    $scripts[] = ['page' => $locale.$path, 'kind' => 'script', 'code' => $script->textContent];
                }
                foreach ($xp->query('//@onsubmit | //@onclick | //@onchange | //@oninput') as $attribute) {
                    $scripts[] = ['page' => $locale.$path, 'kind' => $attribute->name, 'code' => $attribute->value];
                }
            }
        }
        file_put_contents(__DIR__.'/rendered-scripts.json', json_encode($scripts, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $this->assertNotEmpty($scripts);
    }
}
}
