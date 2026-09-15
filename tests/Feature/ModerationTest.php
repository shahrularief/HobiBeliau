<?php
namespace Tests\Feature;
use App\Models\{User, Listing, Order, ModerationLog};
use App\Services\Moderation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class ModerationTest extends TestCase {
    use RefreshDatabase;
    private function setupCard(): array {
        $seller = User::factory()->create();
        $app = $seller->sellerApplication()->create(['shop_name' => 'Shop', 'description' => 'Cards']);
        $app->forceFill(['status' => 'approved'])->save();
        $admin = User::factory()->create(); $admin->forceFill(['is_admin' => true])->save();
        $card = new Listing(['title' => 'Card', 'game' => 'Other', 'condition' => 'Near mint', 'description' => 'Card', 'price' => '10.00', 'quantity' => 5, 'shipping_price' => '2.00', 'shipping_details' => 'Malaysia', 'status' => 'published', 'images' => []]);
        $card->seller_id = $seller->id; $card->save(); return [$seller, $admin, $card];
    }
    private function checkout(): array { return ['checkout_token' => (string)Str::uuid(), 'quantity' => 1, 'recipient' => 'Buyer', 'phone' => '123', 'address' => 'Malaysia']; }
    public function test_admin_can_hide_restore_and_seller_edits_cannot_clear_moderation(): void {
        [$seller, $admin, $card] = $this->setupCard();
        app(Moderation::class)->apply($admin, $card, true, 'Review needed');
        $this->get('/cards/'.$card->id)->assertNotFound();
        $this->actingAs($seller)->put('/seller/listings/'.$card->id, $card->only(['title','game','condition','description','price','quantity','shipping_price','shipping_details','status']) + ['admin_hidden' => false])->assertRedirect();
        $this->assertTrue((bool)$card->fresh()->admin_hidden);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post('/checkout/'.$card->id, $this->checkout())->assertSessionHasErrors('quantity');
        app(Moderation::class)->apply($admin, $card, false, 'Reviewed');
        $this->get('/cards/'.$card->id)->assertOk();
        $this->assertDatabaseCount('moderation_logs', 2);
    }
    public function test_suspension_blocks_new_sales_but_preserves_existing_order_resolution(): void {
        [$seller, $admin, $card] = $this->setupCard(); $buyer = User::factory()->create();
        $order = Order::place($buyer, $card, $this->checkout());
        app(Moderation::class)->apply($admin, $seller, true, 'Seller review');
        $this->assertFalse($seller->isApprovedSeller());
        $this->get('/cards/'.$card->id)->assertNotFound();
        $this->actingAs($seller)->get('/seller/listings/create')->assertForbidden();
        $this->get('/seller/orders')->assertOk();
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('Start processing');
        $this->patch('/orders/'.$order->id, ['status' => 'processing'])->assertRedirect();
        $this->actingAs($buyer)->post('/checkout/'.$card->id, $this->checkout())->assertSessionHasErrors('quantity');
        $this->patch('/orders/'.$order->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(5, $card->fresh()->quantity);
        app(Moderation::class)->apply($admin, $seller, false, 'Access restored');
        $this->assertTrue($seller->isApprovedSeller());
        $this->get('/cards/'.$card->id)->assertOk();
    }
    public function test_buyers_cannot_use_moderation_and_admin_actions_record_reasons(): void {
        [$seller, $admin, $card] = $this->setupCard();
        $this->actingAs($seller)->get('/admin/listings')->assertForbidden();
        try { app(Moderation::class)->apply($seller, $card, true, 'No'); $this->fail('Non-admin moderation must fail'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(403, $exception->getStatusCode()); }
        $this->actingAs($admin)->get('/admin/listings')->assertOk();
        \Livewire\Livewire::test(\App\Filament\Resources\ListingResource\Pages\ListListings::class)->callTableAction('hide', $card, ['reason' => 'Problematic listing']);
        $this->assertTrue((bool)$card->fresh()->admin_hidden);
        $this->assertDatabaseHas('moderation_logs', ['admin_id' => $admin->id, 'reason' => 'Problematic listing', 'action' => 'hide_listing']);
    }
}
