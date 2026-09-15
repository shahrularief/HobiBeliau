<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class CardCatalogTest extends TestCase {
    use RefreshDatabase;
    private function seller(): User {
        $user = User::factory()->create();
        $application = $user->sellerApplication()->create(['shop_name' => 'Shop', 'description' => 'Cards']);
        $application->forceFill(['status' => 'approved'])->save(); return $user;
    }
    public function test_search_and_detail_are_cached_and_only_available_to_approved_sellers(): void {
        Http::fake(['*cards?*' => Http::response([['id' => 'base1-58', 'name' => 'Pikachu', 'localId' => '58']]), '*cards/base1-58' => Http::response(['id' => 'base1-58', 'name' => 'Pikachu', 'localId' => '58', 'set' => ['name' => 'Base Set']])]);
        $this->actingAs(User::factory()->create())->getJson('/seller/catalog/cards?q=Pikachu')->assertForbidden();
        Http::assertNothingSent();
        $this->actingAs($this->seller())->getJson('/seller/catalog/cards?q=Pikachu')->assertOk()->assertJsonPath('0.name', 'Pikachu');
        $this->getJson('/seller/catalog/cards?q=Pikachu')->assertOk();
        $this->getJson('/seller/catalog/cards/base1-58')->assertOk()->assertJsonPath('set', 'Base Set');
        $this->getJson('/seller/catalog/cards/base1-58')->assertOk();
        Http::assertSentCount(2);
    }
    public function test_invalid_queries_and_upstream_failure_have_safe_responses(): void {
        $this->actingAs($this->seller())->getJson('/seller/catalog/cards?q=a')->assertUnprocessable();
        Http::fake(['*' => Http::response([], 503)]);
        $this->getJson('/seller/catalog/cards?q=Pikachu')->assertStatus(503)->assertJsonPath('message', 'Card search is unavailable. Try again or enter your card manually.');
    }
}
