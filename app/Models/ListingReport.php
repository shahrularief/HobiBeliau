<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
class ListingReport extends \Illuminate\Database\Eloquent\Model {
    protected $guarded = ['id'];
    public function listing() { return $this->belongsTo(Listing::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
    public function resolve(User $admin, string $resolution, string $decision = 'dismiss'): void {
        abort_unless($admin->is_admin, 403);
        abort_unless(strlen(trim($resolution)) > 0 && strlen($resolution) <= 2000, 422);
        abort_unless(in_array($decision, ['dismiss', 'hide_listing', 'suspend_seller'], true), 422);
        DB::transaction(function () use ($admin, $resolution, $decision) {
            $updated = static::whereKey($this->id)->where('status', 'open')->update(['status' => 'resolved', 'resolution_action' => $decision, 'resolution' => $resolution, 'resolved_by' => $admin->id, 'resolved_at' => now()]);
            abort_unless($updated === 1, 409);
            if ($decision === 'hide_listing') {
                app(\App\Services\Moderation::class)->apply($admin, $this->listing, true, $resolution);
            } elseif ($decision === 'suspend_seller') {
                app(\App\Services\Moderation::class)->apply($admin, $this->listing->seller, true, $resolution);
            }
            ModerationLog::create(['admin_id' => $admin->id, 'target_type' => 'report', 'target_id' => $this->id, 'action' => 'resolve_report', 'reason' => $resolution]);
            $this->refresh();
        });
    }
}
