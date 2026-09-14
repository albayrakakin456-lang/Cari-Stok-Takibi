<?php

namespace Tests\Audit;

use App\Models\{User, Contact, Product, Sale, Purchase, CashTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Read-only application audit: all writes are to the isolated in-memory test DB. */
class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Audit requires SQLite :memory:.');
        }
    }

    private function fixtures(): array
    {
        $this->actingAs(User::factory()->create());
        $customer = Contact::create(['name' => 'Audit Customer', 'type' => 'customer']);
        $supplier = Contact::create(['name' => 'Audit Supplier', 'type' => 'supplier']);
        $product = Product::create([
            'name' => 'Audit Product', 'code' => 'AUDIT-01', 'purchase_price' => 10,
            'sale_price' => 20, 'tax_rate' => 20, 'stock' => 10, 'min_stock' => 2,
        ]);
        return [$customer, $supplier, $product];
    }

    private function validSale(Contact $customer, Product $product): array
    {
        return ['contact_id' => $customer->id, 'product_id' => [$product->id], 'quantity' => [1], 'unit_price' => [20]];
    }

    public static function locales(): array
    {
        return [['tr'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_empty_account_pages_and_their_links(string $locale): void
    {
        $this->actingAs(User::factory()->create())->withSession(['locale' => $locale]);
        foreach (['/', '/contacts', '/products', '/sales', '/purchases', '/cash', '/contacts/create', '/products/create', '/sales/create', '/purchases/create', '/cash/create'] as $path) {
            $response = $this->get($path);
            $this->assertSame(200, $response->status(), $path);
            $this->checkLinks($response->getContent());
        }
    }

    private function checkLinks(string $html): void
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        foreach ($document->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            $parts = parse_url($href);
            if (!$parts || !isset($parts['path']) || (isset($parts['host']) && $parts['host'] !== parse_url(url('/'), PHP_URL_HOST))) {
                continue;
            }
            $path = $parts['path'];
            if (str_starts_with($path, '/lang/')) {
                continue;
            }
            $response = $this->get($path);
            $this->assertSame(200, $response->status(), 'Broken rendered link: '.$path);
        }
    }

    #[DataProvider('locales')]
    public function test_populated_account_pages_and_links(string $locale): void
    {
        [$customer, $supplier, $product] = $this->fixtures();
        $this->withSession(['locale' => $locale]);
        $this->post('/sales', $this->validSale($customer, $product))->assertSessionHasNoErrors()->assertRedirect('/sales');
        $this->post('/purchases', array_replace($this->validSale($supplier, $product), ['is_paid' => '1']))->assertSessionHasNoErrors()->assertRedirect('/purchases');
        $this->post('/cash', ['type' => 'out', 'amount' => 5, 'description' => 'General expense'])->assertSessionHasNoErrors()->assertRedirect('/cash');
        foreach (['/', '/contacts', '/products', '/sales', '/purchases', '/cash', '/contacts/'.$customer->id, '/contacts/'.$supplier->id, '/products/'.$product->id, '/sales/'.Sale::first()->id, '/purchases/'.Purchase::first()->id] as $path) {
            $response = $this->get($path);
            $this->assertSame(200, $response->status(), $path);
            $this->checkLinks($response->getContent());
        }
    }

    public static function extraRoutes(): array
    {
        $cases = [];
        foreach (['contacts', 'products', 'sales', 'purchases', 'cash'] as $resource) {
            foreach (['GET' => '/1/edit', 'PUT' => '/1', 'PATCH' => '/1'] as $method => $suffix) {
                $cases[$method.' /'.$resource.$suffix] = [$method, '/'.$resource.$suffix];
            }
            if (in_array($resource, ['contacts', 'products', 'cash'])) {
                $cases['DELETE /'.$resource.'/1'] = ['DELETE', '/'.$resource.'/1'];
            }
        }
        $cases['GET /cash/1'] = ['GET', '/cash/1'];
        return $cases;
    }

    #[DataProvider('extraRoutes')]
    public function test_registered_resource_endpoints_do_not_crash(string $method, string $path): void
    {
        // These actions must dispatch safely even when no business records exist.
        $this->actingAs(User::factory()->create());
        $response = $this->call($method, $path);
        $this->assertLessThan(500, $response->status(), $method.' '.$path.' returned '.$response->status());
    }

    public function test_guest_pages_protected_pages_and_logout(): void
    {
        foreach (['/login', '/register'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['/', '/contacts', '/products', '/sales', '/purchases', '/cash'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_locale_switch_returns_to_valid_page(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['tr', 'en'] as $locale) {
            $this->from('/products')->get('/lang/'.$locale)->assertRedirect('/products')->assertSessionHas('locale', $locale);
            $this->get('/products')->assertOk();
        }
    }

    public function test_unknown_and_foreign_records_correctly_return_404(): void
    {
        [$customer, $supplier, $product] = $this->fixtures();
        $sale = Sale::create(['contact_id' => $customer->id, 'invoice_number' => 'AUDIT-S', 'total_amount' => 0]);
        $purchase = Purchase::create(['contact_id' => $supplier->id, 'invoice_number' => 'AUDIT-P', 'total_amount' => 0]);
        $this->actingAs(User::factory()->create());
        foreach (['contacts' => $customer->id, 'products' => $product->id, 'sales' => $sale->id, 'purchases' => $purchase->id] as $resource => $id) {
            $this->get('/'.$resource.'/'.$id)->assertNotFound();
            $this->get('/'.$resource.'/999999')->assertNotFound();
        }
    }

    public function test_empty_forms_return_validation_errors_instead_of_500(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['contacts', 'products', 'sales', 'purchases', 'cash'] as $resource) {
            $this->from('/'.$resource.'/create')->post('/'.$resource, [])->assertRedirect('/'.$resource.'/create')->assertSessionHasErrors();
        }
    }

    public function test_misaligned_sale_quantities_are_rejected_without_500(): void
    {
        [$customer, , $product] = $this->fixtures();
        $payload = $this->validSale($customer, $product);
        $payload['product_id'] = [$product->id, $product->id];
        $payload['unit_price'] = [20, 20];
        $response = $this->from('/sales/create')->post('/sales', $payload);
        $this->assertLessThan(500, $response->status(), 'Missing second quantity produced HTTP '.$response->status());
        $response->assertSessionHasErrors();
    }

    public function test_nested_product_id_is_rejected_without_500(): void
    {
        [$customer, , $product] = $this->fixtures();
        $payload = $this->validSale($customer, $product);
        $payload['product_id'] = [[$product->id]];
        $response = $this->from('/sales/create')->post('/sales', $payload);
        $this->assertLessThan(500, $response->status(), 'Nested product ID produced HTTP '.$response->status());
        $response->assertSessionHasErrors();
    }

    public function test_duplicate_product_rows_cannot_oversell_stock(): void
    {
        [$customer, , $product] = $this->fixtures();
        $this->from('/sales/create')->post('/sales', [
            'contact_id' => $customer->id, 'product_id' => [$product->id, $product->id],
            'quantity' => [6, 6], 'unit_price' => [20, 20],
        ]);
        $this->assertGreaterThanOrEqual(0, $product->fresh()->stock, 'Two rows of 6 oversold the stock of 10.');
    }

    public function test_sale_cancellation_restores_stock_and_cash(): void
    {
        [$customer, , $product] = $this->fixtures();
        $this->post('/sales', $this->validSale($customer, $product))->assertSessionHasNoErrors();
        $id = Sale::firstOrFail()->id;
        $this->delete('/sales/'.$id)->assertRedirect('/sales')->assertSessionHasNoErrors();
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertEquals(0, $this->cashBalance());
        $this->get('/sales')->assertOk();
    }

    private function cashBalance(): float
    {
        return CashTransaction::where('type', 'in')->sum('amount') - CashTransaction::where('type', 'out')->sum('amount');
    }

    public function test_paid_purchase_cancellation_restores_cash(): void
    {
        [, $supplier, $product] = $this->fixtures();
        $this->post('/purchases', array_replace($this->validSale($supplier, $product), ['is_paid' => '1']))->assertSessionHasNoErrors();
        $id = Purchase::firstOrFail()->id;
        $this->delete('/purchases/'.$id)->assertRedirect('/purchases')->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->cashBalance(), 'Paid purchase was cancelled but the cash outflow was not reversed.');
    }

    public function test_purchase_cancellation_cannot_make_stock_negative(): void
    {
        [$customer, $supplier, $product] = $this->fixtures();
        $product->update(['stock' => 0]);
        $this->post('/purchases', array_replace($this->validSale($supplier, $product), ['quantity' => [5]]))->assertSessionHasNoErrors();
        $id = Purchase::firstOrFail()->id;
        $this->post('/sales', array_replace($this->validSale($customer, $product), ['quantity' => [5]]))->assertSessionHasNoErrors();
        $this->delete('/purchases/'.$id);
        $this->assertGreaterThanOrEqual(0, $product->fresh()->stock, 'Cancelling an already-sold purchase created negative stock.');
    }

    public function test_contact_list_and_details_show_same_balance(): void
    {
        [, $supplier, $product] = $this->fixtures();
        $this->post('/purchases', $this->validSale($supplier, $product))->assertSessionHasNoErrors();
        $details = $this->get('/contacts/'.$supplier->id)->assertOk();
        $list = $this->get('/contacts')->assertOk();
        $listed = $list->viewData('contacts')->firstWhere('id', $supplier->id);
        $this->assertEquals($details->viewData('balance'), $listed->balance, 'List balance differs from the statement balance.');
    }

    public function test_registration_with_long_password_is_handled_without_500(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Audit Password', 'email' => 'audit-password@example.test',
            'password' => str_repeat('a', 100), 'password_confirmation' => str_repeat('a', 100),
        ]);
        $this->assertLessThan(500, $response->status(), '100-character password produced HTTP '.$response->status());
    }
}
