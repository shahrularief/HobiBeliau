<?php
namespace Tests\Feature;
use App\Models\{Listing, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class OrderTest extends TestCase {
    use RefreshDatabase;
    private function card(): Listing {
        $seller = User::factory()->create();
        $app = $seller->sellerApplication()->create(['shop_name' => 'Shop', 'description' => 'Cards']);
        $app->forceFill(['status' => 'approved'])->save();
        $card = new Listing(['title' => 'Test card', 'game' => 'Other', 'condition' => 'Near mint', 'description' => 'Card', 'price' => '10.25', 'quantity' => 2, 'shipping_price' => '5.00', 'shipping_details' => 'Malaysia', 'images' => [], 'status' => 'published']);
        $card->seller_id = $seller->id; $card->save(); return $card;
    }
    private function data(array $override = []): array { return array_replace(['checkout_token' => (string) Str::uuid(), 'quantity' => 1, 'recipient' => 'Buyer', 'phone' => '0123456789', 'address' => 'Test address, Malaysia'], $override); }
    public function test_checkout_snapshots_prices_reserves_inventory_and_is_idempotent(): void {
        $card = $this->card(); $buyer = User::factory()->create(); $data = $this->data(['quantity' => 2, 'total_cents' => 1, 'payment_status' => 'paid']);
        $this->actingAs($buyer)->get('/checkout/'.$card->id)->assertOk()->assertSee('No payment is collected');
        $this->post('/checkout/'.$card->id, $data)->assertRedirect('/orders/1');
        $order = Order::firstOrFail();
        $this->assertSame(2550, (int) $order->total_cents);
        $this->assertSame('not_collected', $order->payment_status);
        $this->assertSame(0, $card->fresh()->quantity);
        $this->post('/checkout/'.$card->id, $data)->assertRedirect('/orders/1');
        $this->assertDatabaseCount('orders', 1);
        $card->update(['price' => '99.00', 'title' => 'Edited']);
        $this->assertSame(1025, (int) $order->items->first()->unit_price_cents);
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('Test card');
        $this->get('/orders')->assertOk();
    }
    public function test_insufficient_inventory_unpublished_and_self_orders_are_blocked(): void {
        $card = $this->card(); $buyer = User::factory()->create();
        $this->actingAs($buyer)->post('/checkout/'.$card->id, $this->data(['quantity' => 3]))->assertSessionHasErrors('quantity');
        $card->update(['status' => 'paused']);
        $this->post('/checkout/'.$card->id, $this->data())->assertSessionHasErrors('quantity');
        $card->update(['status' => 'published']);
        $this->actingAs($card->seller)->post('/checkout/'.$card->id, $this->data())->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $card->fresh()->quantity);
    }
    public function test_cancellation_restores_stock_once_and_order_access_is_private(): void {
        $card = $this->card(); $buyer = User::factory()->create(); $order = Order::place($buyer, $card, $this->data());
        $this->actingAs(User::factory()->create())->get('/orders/'.$order->id)->assertForbidden();
        $this->patch('/orders/'.$order->id, ['status' => 'cancelled'])->assertForbidden();
        $this->actingAs($buyer)->patch('/orders/'.$order->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(2, $card->fresh()->quantity);
        $this->patch('/orders/'.$order->id, ['status' => 'cancelled'])->assertStatus(409);
        $this->assertSame(2, $card->fresh()->quantity);
    }
    public function test_fulfilment_transitions_require_the_correct_actor_and_tracking(): void {
        $card = $this->card(); $buyer = User::factory()->create(); $order = Order::place($buyer, $card, $this->data());
        $path = '/orders/'.$order->id;
        $this->actingAs($buyer)->patch($path, ['status' => 'processing'])->assertForbidden();
        $this->actingAs($card->seller)->get('/seller/orders')->assertOk();
        $this->patch($path, ['status' => 'shipped', 'tracking' => 'Carrier 123'])->assertStatus(409);
        $this->patch($path, ['status' => 'processing'])->assertRedirect();
        $this->patch($path, ['status' => 'shipped'])->assertStatus(422);
        $this->patch($path, ['status' => 'shipped', 'tracking' => 'Carrier 123'])->assertRedirect();
        $this->patch($path, ['status' => 'completed'])->assertForbidden();
        $this->actingAs($buyer)->patch($path, ['status' => 'cancelled'])->assertStatus(409);
        $this->patch($path, ['status' => 'completed'])->assertRedirect();
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('not_collected', $order->fresh()->payment_status);
    }
    public function test_admin_can_inspect_orders_but_buyer_cannot_access_admin(): void {
        $card = $this->card(); $buyer = User::factory()->create(); $order = Order::place($buyer, $card, $this->data());
        $this->actingAs($buyer)->get('/admin/orders')->assertForbidden();
        $admin = User::factory()->create(); $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin/orders')->assertOk();
        \Livewire\Livewire::test(\App\Filament\Resources\OrderResource\Pages\ListOrders::class)->mountTableAction('inspect', $order)->assertSee('Test card');
    }
    public function test_simulated_payment_is_buyer_only_retryable_and_does_not_change_stock(): void {
        $card = $this->card(); $buyer = User::factory()->create(); $order = Order::place($buyer, $card, $this->data());
        $path = '/orders/'.$order->id.'/demo-payment';
        $this->actingAs($card->seller)->post($path, ['outcome' => 'success'])->assertForbidden();
        $this->actingAs($buyer)->post($path, ['outcome' => 'failure'])->assertRedirect();
        $this->assertSame('simulated_failure', $order->fresh()->payment_status);
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('Simulate success');
        $this->post($path, ['outcome' => 'success'])->assertRedirect();
        $this->assertSame('simulated_success', $order->fresh()->payment_status);
        $this->post($path, ['outcome' => 'failure'])->assertStatus(409);
        $this->assertSame(1, $card->fresh()->quantity);
        $this->patch('/orders/'.$order->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(2, $card->fresh()->quantity);
        $this->post($path, ['outcome' => 'success'])->assertStatus(409);
    }
}