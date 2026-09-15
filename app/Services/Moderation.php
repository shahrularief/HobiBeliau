<?php
namespace App\Services;
use App\Models\{User, Listing, ModerationLog};
use Illuminate\Support\Facades\DB;
class Moderation {
    public function apply(User $admin, User|Listing $target, bool $restricted, string $reason): void {
        abort_unless($admin->is_admin, 403);
        $reason = trim($reason);
        abort_unless(strlen($reason) > 0 && strlen($reason) <= 2000, 422, 'A reason is required.');
        DB::transaction(function () use ($admin, $target, $restricted, $reason) {
            $record = $target->newQuery()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            $isUser = $record instanceof User;
            $record->forceFill([$isUser ? 'selling_suspended' : 'admin_hidden' => $restricted])->save();
            ModerationLog::create(['admin_id' => $admin->id, 'target_type' => $isUser ? 'user' : 'listing', 'target_id' => $record->id, 'action' => $isUser ? ($restricted ? 'suspend_selling' : 'restore_selling') : ($restricted ? 'hide_listing' : 'restore_listing'), 'reason' => $reason]);
            $target->refresh();
        });
    }
}
