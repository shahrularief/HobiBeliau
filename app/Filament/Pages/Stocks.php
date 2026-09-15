<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class Stocks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static string $view = 'filament.pages.stocks';

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory'; // ✅ Updated group name
    }

    public static function getNavigationLabel(): string
    {
        return "Stocks"; // ✅ Updated page label
    }

    public function getStockItems(): array
    {
        return Stock::all()->toArray();
    }

    public function deleteStock($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-stocks');
        $stock = Stock::findOrFail($id);
        if ($stock->image) {
            Storage::disk('public')->delete($stock->image);
        }
        $stock->delete();
        session()->flash('success', 'Stock deleted successfully!');
    }
}
