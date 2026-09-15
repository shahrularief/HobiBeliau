<?php

namespace App\Filament\Resources;

use App\Models\SellerApplication;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SellerApplicationResource extends Resource
{
    protected static ?string $model = SellerApplication::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('shop_name')->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label('Applicant'),
            Tables\Columns\TextColumn::make('user.email')->label('Email'),
            Tables\Columns\TextColumn::make('description')->wrap(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\IconColumn::make('user.selling_suspended')->boolean()->label('Selling suspended'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
            Tables\Columns\TextColumn::make('reviewed_at')->dateTime(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
        ])->actions([
            Tables\Actions\Action::make('suspendSelling')->label('Suspend selling')->requiresConfirmation()->visible(fn (SellerApplication $record) => $record->status === 'approved' && !$record->user->selling_suspended)
                ->form([\Filament\Forms\Components\Textarea::make('reason')->required()->maxLength(2000)])
                ->action(fn (SellerApplication $record, array $data) => app(\App\Services\Moderation::class)->apply(auth()->user(), $record->user, true, $data['reason'])),
            Tables\Actions\Action::make('restoreSelling')->label('Restore selling')->requiresConfirmation()->visible(fn (SellerApplication $record) => (bool)$record->user->selling_suspended)
                ->form([\Filament\Forms\Components\Textarea::make('reason')->required()->maxLength(2000)])
                ->action(fn (SellerApplication $record, array $data) => app(\App\Services\Moderation::class)->apply(auth()->user(), $record->user, false, $data['reason'])),
            Tables\Actions\Action::make('approve')->color('success')->requiresConfirmation()
                ->visible(fn (SellerApplication $record) => $record->status === 'pending')
                ->action(fn (SellerApplication $record) => $record->review(auth()->user(), 'approved')),
            Tables\Actions\Action::make('reject')->color('danger')->requiresConfirmation()
                ->visible(fn (SellerApplication $record) => $record->status === 'pending')
                ->action(fn (SellerApplication $record) => $record->review(auth()->user(), 'rejected')),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SellerApplicationResource\Pages\ListSellerApplications::route('/')];
    }
}
