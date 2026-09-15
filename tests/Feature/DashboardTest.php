<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_only_includes_the_buyers_own_orders(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $seller = User::factory()->create();
        foreach ([$buyer, $other] as $owner) {
            $order = Order::create(['buyer_id' => $owner->id, 'seller_id' => $seller->id, 'checkout_token' => (string) \Illuminate\Support\Str::uuid(), 'recipient' => 'Demo', 'phone' => '123', 'address' => 'Demo address', 'subtotal_cents' => 1000, 'shipping_cents' => 0, 'total_cents' => 1000, 'status' => 'placed', 'payment_status' => 'not_collected']);
        }
        $this->actingAs($buyer)->get('/dashboard')->assertOk()
            ->assertViewHas('purchaseCount', 1)
            ->assertViewHas('recentPurchases', fn ($orders) => $orders->count() === 1 && $orders->first()->buyer_id === $buyer->id)
            ->assertSee('Your vault, in motion.')->assertDontSee('Admin control room');
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
