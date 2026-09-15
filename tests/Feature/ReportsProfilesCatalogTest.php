<?php
namespace Tests\Feature;
use App\Models\{User,Listing,ListingReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class ReportsProfilesCatalogTest extends TestCase {
    use RefreshDatabase;
    private function card(): Listing {
        $seller = User::factory()->create();
        $app = $seller->sellerApplication()->create(['shop_name'=>'Shop','description'=>'Private application']);
        $app->forceFill(['status'=>'approved'])->save();
        $card = new Listing(['title'=>'Pikachu','game'=>'Pokémon','set_name'=>'Base Set','condition'=>'Near mint','description'=>'Card','price'=>'10.00','quantity'=>5,'shipping_price'=>'2.00','shipping_details'=>'Malaysia','status'=>'published','images'=>[]]);
        $card->seller_id=$seller->id; $card->save(); return $card;
    }
    public function test_reports_are_authenticated_idempotent_and_admin_resolutions_audited(): void {
        $card=$this->card(); $path='/cards/'.$card->id.'/report';
        $this->post($path,['reason'=>'Suspicious card details'])->assertRedirect('/login');
        $buyer=User::factory()->create();
        $this->actingAs($buyer)->post($path,['reason'=>'Suspicious card details','status'=>'resolved'])->assertRedirect();
        $this->post($path,['reason'=>'Duplicate report'])->assertRedirect();
        $this->assertDatabaseCount('listing_reports',1);
        $report=ListingReport::firstOrFail(); $this->assertSame('open',$report->status);
        $this->get('/admin/listing-reports')->assertForbidden();
        $admin=User::factory()->create(); $admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->get('/admin/listing-reports')->assertOk();
        \Livewire\Livewire::test(\App\Filament\Resources\ListingReportResource\Pages\ListListingReports::class)->callTableAction('resolve',$report,['resolution'=>'Inspected and addressed']);
        $this->assertSame('resolved',$report->fresh()->status);
        $this->assertDatabaseHas('moderation_logs',['action'=>'resolve_report','admin_id'=>$admin->id]);
        $this->get('/admin/moderation-logs')->assertOk();
    }
    public function test_shop_edits_are_owned_and_application_details_are_private(): void {
        $card=$this->card(); $seller=$card->seller;
        $this->get('/shops/'.$seller->id)->assertOk()->assertDontSee('Private application');
        $this->actingAs(User::factory()->create())->put('/seller/profile',['shop_name'=>'Intruder'])->assertForbidden();
        $this->actingAs($seller)->put('/seller/profile',['shop_name'=>'New shop','public_bio'=>'Public intro','location'=>'Selangor','shipping_policy'=>'Ships on weekdays','status'=>'rejected','user_id'=>999])->assertRedirect();
        $this->assertSame('approved',$seller->sellerApplication->fresh()->status);
        $this->get('/shops/'.$seller->id)->assertOk()->assertSee('Public intro')->assertSee('Selangor');
    }
    public function test_selected_catalog_printing_is_verified_and_saved(): void {
        $card=$this->card();
        Http::fake(['*'=>Http::response(['id'=>'base1-58','name'=>'Pikachu','localId'=>'58','rarity'=>'Common','set'=>['id'=>'base1','name'=>'Base Set']])]);
        $data=$card->only(['title','game','set_name','condition','description','price','quantity','shipping_price','shipping_details','status']);
        $this->actingAs($card->seller)->put('/seller/listings/'.$card->id,$data+['tcgdex_id'=>'base1-58','rarity'=>'Fake'])->assertRedirect();
        $this->assertSame('base1-58',$card->fresh()->tcgdex_id);
        $this->assertSame('Common',$card->fresh()->rarity);
        $this->put('/seller/listings/'.$card->id,array_replace($data,['title'=>'Wrong','tcgdex_id'=>'base1-58']))->assertSessionHasErrors('tcgdex_id');
        $this->get('/cards/'.$card->id)->assertSee('base1-58');
    }
}
