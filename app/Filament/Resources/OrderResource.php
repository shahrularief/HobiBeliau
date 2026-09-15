<?php
namespace App\Filament\Resources;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class OrderResource extends Resource {
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    public static function canViewAny(): bool { return (bool) auth()->user()?->is_admin; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('Order'),
            Tables\Columns\TextColumn::make('buyer.email')->label('Buyer')->searchable(),
            Tables\Columns\TextColumn::make('seller.email')->label('Seller')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('payment_status')->label('Payment'),
            Tables\Columns\TextColumn::make('total_cents')->label('Total')->formatStateUsing(fn ($state) => 'RM '.number_format($state / 100, 2)),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->actions([
            Tables\Actions\Action::make('inspect')->modalHeading('Development order details')->modalContent(fn (Order $record) => view('orders.admin-details', ['order' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
        ])->defaultSort('created_at', 'desc');
    }
    public static function getPages(): array { return ['index' => OrderResource\Pages\ListOrders::route('/')]; }
}
