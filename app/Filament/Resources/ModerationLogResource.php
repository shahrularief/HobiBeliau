<?php
namespace App\Filament\Resources;
use App\Models\ModerationLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class ModerationLogResource extends Resource {
    protected static ?string $model = ModerationLog::class;
    public static function canViewAny(): bool { return (bool) auth()->user()?->is_admin; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('admin.email')->label('Administrator')->searchable(),
            Tables\Columns\TextColumn::make('target_type'),
            Tables\Columns\TextColumn::make('target_id'),
            Tables\Columns\TextColumn::make('action')->searchable(),
            Tables\Columns\TextColumn::make('reason')->wrap(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->filters([Tables\Filters\SelectFilter::make('target_type')->options(['listing' => 'Listing', 'user' => 'User', 'report' => 'Report'])])->defaultSort('id', 'desc');
    }
    public static function getPages(): array { return ['index' => ModerationLogResource\Pages\ListModerationLogs::route('/')]; }
}
