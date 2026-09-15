<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerApplication extends Model
{
    protected $fillable = ['shop_name', 'description'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(User $admin, string $status): void
    {
        abort_unless($admin->is_admin, 403);
        abort_unless(in_array($status, ['approved', 'rejected'], true), 422);
        $updated = static::whereKey($this->id)->where('status', 'pending')->update([
            'status' => $status,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
        abort_unless($updated === 1, 409, 'This application has already been reviewed.');
        $this->refresh();
    }
}
