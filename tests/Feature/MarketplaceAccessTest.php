<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyers_cannot_access_admin_or_delete_stock(): void
    {
        $buyer = User::factory()->create();
        $stock = Stock::create(['title' => 'Card', 'quantity' => 1]);
        $this->actingAs($buyer)->get('/admin')->assertForbidden();
        $this->delete('/delete-stock/'.$stock->id)->assertForbidden();
        $this->assertDatabaseHas('stocks', ['id' => $stock->id]);
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_guests_cannot_apply_or_delete_stock(): void
    {
        $this->get('/seller/apply')->assertRedirect('/login');
        $this->delete('/delete-stock/1')->assertRedirect('/login');
    }

    public function test_application_ignores_privileged_input_and_cannot_be_overwritten(): void
    {
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post('/seller/apply', [
            'shop_name' => 'My cards', 'description' => 'Selling my collection',
            'status' => 'approved', 'is_admin' => true,
        ])->assertRedirect('/seller/apply');
        $this->assertFalse($buyer->fresh()->is_admin);
        $this->assertFalse($buyer->isApprovedSeller());
        $this->post('/seller/apply', ['shop_name' => 'Changed', 'description' => 'Changed']);
        $this->assertDatabaseCount('seller_applications', 1);
        $this->assertDatabaseHas('seller_applications', ['shop_name' => 'My cards', 'status' => 'pending']);
        $this->get('/seller/apply')->assertOk()->assertSee('My cards');
    }

    public function test_only_admin_can_review_and_a_review_cannot_be_repeated(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $application = $buyer->sellerApplication()->create(['shop_name' => 'Cards', 'description' => 'Collection']);
        try {
            $application->review($buyer, 'approved');
            $this->fail('Buyer review should be forbidden.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $application->review($admin, 'approved');
        $this->assertTrue($buyer->isApprovedSeller());
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->actingAs($admin)->get('/admin/seller-applications')->assertOk();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $application->review($admin, 'rejected');
    }
}
