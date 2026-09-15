<?php
namespace Tests\Feature;
use App\Models\{User,Listing,ListingReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ReportDecisionTest extends TestCase {
    use RefreshDatabase;
    private function report(): array {
        $seller=User::factory()->create();
        $application=$seller->sellerApplication()->create(['shop_name'=>'Shop','description'=>'Cards']);
        $application->forceFill(['status'=>'approved'])->save();
        $admin=User::factory()->create(); $admin->forceFill(['is_admin'=>true])->save();
        $card=new Listing(['title'=>'Card','game'=>'Other','condition'=>'Near mint','description'=>'Card','price'=>10,'quantity'=>5,'shipping_price'=>2,'shipping_details'=>'Malaysia','images'=>[],'status'=>'published']);
        $card->seller_id=$seller->id; $card->save();
        $report=ListingReport::create(['listing_id'=>$card->id,'reporter_id'=>User::factory()->create()->id,'reason'=>'Problematic listing']);
        return [$report->refresh(),$admin,$card,$seller];
    }
    public function test_hide_decision_restricts_listing_and_audits_both_actions(): void {
        [$report,$admin,$card]=$this->report();
        $this->actingAs($admin)->get('/admin/listing-reports')->assertOk();
        \Livewire\Livewire::test(\App\Filament\Resources\ListingReportResource\Pages\ListListingReports::class)->callTableAction('resolve',$report,['decision'=>'hide_listing','resolution'=>'Incorrect listing']);
        $this->assertTrue((bool)$card->fresh()->admin_hidden);
        $this->assertSame('hide_listing',$report->fresh()->resolution_action);
        $this->assertDatabaseHas('moderation_logs',['action'=>'hide_listing']);
        $this->assertDatabaseHas('moderation_logs',['action'=>'resolve_report']);
    }
    public function test_suspension_and_dismissal_have_distinct_effects(): void {
        [$report,$admin,$card,$seller]=$this->report();
        $report->resolve($admin,'Seller review required','suspend_seller');
        $this->assertTrue((bool)$seller->fresh()->selling_suspended);
        $this->assertFalse(Listing::visible()->whereKey($card->id)->exists());
        [$other,$admin2,$card2,$seller2]=$this->report();
        $other->resolve($admin2,'No issue found','dismiss');
        $this->assertFalse((bool)$seller2->fresh()->selling_suspended);
        $this->assertFalse((bool)$card2->fresh()->admin_hidden);
    }
    public function test_resolved_report_cannot_apply_another_restriction(): void {
        [$report,$admin,$card]=$this->report();
        $report->resolve($admin,'No issue found','dismiss');
        try { $report->resolve($admin,'Second decision','hide_listing'); $this->fail('Repeated resolution must fail'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(409,$exception->getStatusCode()); }
        $this->assertFalse((bool)$card->fresh()->admin_hidden);
        $this->assertDatabaseCount('moderation_logs',1);
    }
}
