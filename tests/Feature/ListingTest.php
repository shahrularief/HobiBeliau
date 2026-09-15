<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;
    private function seller(): User
    {
        $user = User::factory()->create();
        $application = $user->sellerApplication()->create(['shop_name' => 'Card shop', 'description' => 'Collection']);
        $application->forceFill(['status' => 'approved'])->save();
        return $user;
    }
    private function data(array $overrides = []): array
    {
        return array_replace(['title' => 'Pikachu', 'game' => 'Pokémon', 'set_name' => 'Base set', 'condition' => 'Near mint', 'description' => 'My card', 'price' => '12.50', 'quantity' => 2, 'shipping_price' => '5.00', 'shipping_details' => 'Malaysia', 'status' => 'published'], $overrides);
    }
    private function listing(User $seller, array $overrides = []): Listing
    {
        $listing = new Listing($this->data($overrides) + ['images' => ['listings/card.png']]);
        $listing->seller_id = $seller->id;
        $listing->save();
        return $listing;
    }
    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('card.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9S8AAAAASUVORK5CYII='));
    }
    public function test_only_approved_sellers_can_manage_listings(): void
    {
        $this->get('/seller/listings')->assertRedirect('/login');
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->get('/seller/listings')->assertForbidden();
        $this->post('/seller/listings', $this->data())->assertForbidden();
        $buyer->sellerApplication()->create(['shop_name' => 'Pending', 'description' => 'Shop']);
        $this->get('/seller/listings/create')->assertForbidden();
    }
    public function test_seller_can_create_a_listing_without_assigning_another_owner(): void
    {
        Storage::fake('public');
        $seller = $this->seller();
        $this->actingAs($seller)->get('/seller/listings/create')->assertOk()->assertDontSee('id="status"', false);
        $data = $this->data(['photos' => [$this->photo()], 'seller_id' => 999]);
        unset($data['status']);
        $this->post('/seller/listings', $data)->assertRedirect('/seller/listings');
        $listing = Listing::firstOrFail();
        $this->assertSame($seller->id, $listing->seller_id);
        $this->assertSame('12.50', $listing->price);
        $this->assertSame('published', $listing->status);
        $this->assertTrue(Listing::visible()->whereKey($listing->id)->exists());
        Storage::disk('public')->assertExists($listing->images[0]);
        $this->get('/seller/listings')->assertOk()->assertSee('Pikachu');
        $this->get('/seller/listings/'.$listing->id.'/edit')->assertOk()->assertSee('id="status"', false);
    }
    public function test_sellers_cannot_edit_other_sellers_listings(): void
    {
        $listing = $this->listing($this->seller());
        $this->actingAs($this->seller())->get('/seller/listings/'.$listing->id.'/edit')->assertForbidden();
        $this->put('/seller/listings/'.$listing->id, $this->data(['title' => 'Changed']))->assertForbidden();
        $this->assertSame('Pikachu', $listing->fresh()->title);
    }
    public function test_replacement_photos_remove_old_photos_and_pausing_hides_listing(): void
    {
        Storage::fake('public');
        $seller = $this->seller();
        $listing = $this->listing($seller);
        Storage::disk('public')->put('listings/card.png', 'old');
        $this->actingAs($seller)->put('/seller/listings/'.$listing->id, $this->data(['photos' => [$this->photo()], 'status' => 'paused']))->assertRedirect('/seller/listings');
        Storage::disk('public')->assertMissing('listings/card.png');
        Storage::disk('public')->assertExists($listing->fresh()->images[0]);
        $this->get('/cards/'.$listing->id)->assertNotFound();
    }
    public function test_public_visibility_and_filters_do_not_expose_private_listings(): void
    {
        $seller = $this->seller();
        $visible = $this->listing($seller);
        $this->listing($seller, ['title' => 'Draft card', 'status' => 'draft']);
        $this->listing($seller, ['title' => 'Sold card', 'status' => 'sold']);
        $this->listing($seller, ['title' => 'Empty card', 'quantity' => 0]);
        $this->get('/marketplace')->assertOk()->assertSee('Pikachu')->assertDontSee('Draft card')->assertDontSee('Sold card')->assertDontSee('Empty card');
        $this->get('/marketplace?q=NoMatch')->assertDontSee('Pikachu');
        $this->get('/marketplace?game=Other')->assertDontSee('Pikachu');
        $this->get('/cards/'.$visible->id)->assertOk()->assertSee('RM 12.50');
        $this->get('/shops/'.$seller->id)->assertOk()->assertSee('Card shop');
        $seller->sellerApplication()->update(['status' => 'rejected']);
        $this->get('/cards/'.$visible->id)->assertNotFound();
    }
    public function test_invalid_price_empty_published_stock_and_non_image_upload_are_rejected(): void
    {
        $seller = $this->seller();
        $listing = $this->listing($seller);
        $this->actingAs($seller)->put('/seller/listings/'.$listing->id, $this->data(['price' => '-1']))->assertSessionHasErrors('price');
        $this->put('/seller/listings/'.$listing->id, $this->data(['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->post('/seller/listings', $this->data(['photos' => [UploadedFile::fake()->create('script.html', 1, 'text/html')]]))->assertSessionHasErrors('photos.0');
    }

    public function test_marketplace_price_filters_and_sorting(): void
    {
        $seller = $this->seller();
        $this->listing($seller, ['title' => 'Cheap card', 'price' => '5.00']);
        $this->listing($seller, ['title' => 'Mid card', 'price' => '15.00']);
        $this->listing($seller, ['title' => 'Expensive card', 'price' => '50.00']);
        $this->get('/marketplace?min_price=10&max_price=20')->assertOk()->assertSee('Mid card')->assertDontSee('Cheap card')->assertDontSee('Expensive card');
        $this->get('/marketplace?sort=price_asc')->assertSeeInOrder(['Cheap card', 'Mid card', 'Expensive card']);
        $this->get('/marketplace?sort=price_desc')->assertSeeInOrder(['Expensive card', 'Mid card', 'Cheap card']);
        $this->getJson('/marketplace?min_price=20&max_price=10')->assertUnprocessable();
    }
}
