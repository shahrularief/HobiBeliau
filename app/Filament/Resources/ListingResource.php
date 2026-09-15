<?php
namespace App\Filament\Resources;
use App\Models\Listing;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class ListingResource extends Resource {
    protected static ?string $model = Listing::class;
    public static function canViewAny(): bool { return (bool) auth()->user()?->is_admin; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('seller.email')->label('Seller')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\IconColumn::make('admin_hidden')->boolean()->label('Hidden by admin'),
            Tables\Columns\TextColumn::make('quantity'),
        ])->actions([
            Tables\Actions\Action::make('inspect')->modalContent(fn (Listing $record) => view('filament.listing-details', ['listing' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            Tables\Actions\Action::make('hide')->visible(fn (Listing $record) => !$record->admin_hidden)->requiresConfirmation()->form([\Filament\Forms\Components\Textarea::make('reason')->required()->maxLength(2000)])
                ->action(fn (Listing $record, array $data) => app(\App\Services\Moderation::class)->apply(auth()->user(), $record, true, $data['reason'])),
            Tables\Actions\Action::make('restore')->visible(fn (Listing $record) => (bool)$record->admin_hidden)->requiresConfirmation()->form([\Filament\Forms\Components\Textarea::make('reason')->required()->maxLength(2000)])
                ->action(fn (Listing $record, array $data) => app(\App\Services\Moderation::class)->apply(auth()->user(), $record, false, $data['reason'])),
        ]);
    }
    public static function getPages(): array { return ['index' => ListingResource\Pages\ListListings::route('/')]; }
}
