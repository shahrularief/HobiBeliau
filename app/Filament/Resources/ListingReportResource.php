<?php
namespace App\Filament\Resources;
use App\Models\ListingReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class ListingReportResource extends Resource {
    protected static ?string $model = ListingReport::class;
    public static function canViewAny(): bool { return (bool) auth()->user()?->is_admin; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('listing.title')->searchable(),
            Tables\Columns\TextColumn::make('reporter.email'),
            Tables\Columns\TextColumn::make('reason')->wrap(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('resolution')->wrap(),
            Tables\Columns\TextColumn::make('resolution_action')->label('Decision')->formatStateUsing(fn ($state) => match ($state) { 'dismiss' => 'Dismissed', 'hide_listing' => 'Listing hidden', 'suspend_seller' => 'Selling suspended', default => 'Recorded note' }),
            Tables\Columns\TextColumn::make('resolver.email')->label('Resolved by'),
            Tables\Columns\TextColumn::make('resolved_at')->dateTime(),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['open' => 'Open', 'resolved' => 'Resolved'])])->actions([
            Tables\Actions\Action::make('resolve')->visible(fn (ListingReport $record) => $record->status === 'open')->requiresConfirmation()
                ->modalHeading('Resolve report')->modalSubmitActionLabel('Apply decision and resolve')
                ->form([
                    \Filament\Forms\Components\Select::make('decision')->label('Action')->required()->default('dismiss')->options([
                        'dismiss' => 'Dismiss report — no restriction',
                        'hide_listing' => 'Hide this listing',
                        'suspend_seller' => 'Suspend seller’s selling access',
                    ])->helperText('Suspension hides all of this seller’s cards and blocks new sales. Existing orders remain accessible. Dismissal does not undo existing restrictions.'),
                    \Filament\Forms\Components\Textarea::make('resolution')->label('Reason for your decision')->required()->maxLength(2000),
                ])
                ->action(fn (ListingReport $record, array $data) => $record->resolve(auth()->user(), $data['resolution'], $data['decision'])),
            Tables\Actions\Action::make('inspectListing')->modalContent(fn (ListingReport $record) => view('filament.listing-details', ['listing' => $record->listing]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
        ])->defaultSort('created_at', 'desc');
    }
    public static function getPages(): array { return ['index' => ListingReportResource\Pages\ListListingReports::route('/')]; }
}
