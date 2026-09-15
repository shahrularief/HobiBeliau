<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    public const GAMES = ['Pokémon', 'One Piece', 'Magic: The Gathering', 'Yu-Gi-Oh!', 'Other'];
    public const CONDITIONS = ['Near mint', 'Lightly played', 'Moderately played', 'Heavily played', 'Damaged'];
    public const STATUSES = ['draft', 'published', 'paused', 'sold'];
    protected $fillable = ['title', 'game', 'set_name', 'condition', 'description', 'price', 'quantity', 'shipping_price', 'shipping_details', 'images', 'status'];
    protected function casts(): array { return ['images' => 'array', 'price' => 'decimal:2', 'shipping_price' => 'decimal:2', 'quantity' => 'integer']; }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('admin_hidden', false)->whereHas('seller', fn (Builder $seller) => $seller->where('selling_suspended', false))->where('status', 'published')->where('quantity', '>', 0)
            ->whereHas('seller.sellerApplication', fn (Builder $application) => $application->where('status', 'approved'));
    }
}
