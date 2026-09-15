<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use Filament\Forms\Concerns\InteractsWithForms; // ✅ Add this
use Filament\Forms\Contracts\HasForms; // ✅ Add this
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;

class AddStock extends Page implements HasForms // ✅ Implement HasForms
{
    use InteractsWithForms; // ✅ Use this trait

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static string $view = 'filament.pages.add-stock';

    public ?Stock $stock = null;
    public ?array $data = []; // ✅ Store form data

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Stock Information')
                    ->schema([
                        TextInput::make('title')
                            ->label('Stock Title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->nullable(),

                        FileUpload::make('image')
                            ->disk('public')
                            ->label('Stock Image')
                            ->image()
                            ->directory('stocks') // Saves images in storage/app/public/stocks
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Actions::make([
                             // ✅ Save Stock Button
                            Action::make('save')
                                ->label('Save Stock')
                                ->submit('save')
                                ->color('primary'),

                         // ✅ Cancel Button
                        Action::make('cancel')
                                ->label('Cancel')
                                ->color('gray')
                                ->url(fn () => route('filament.admin.pages.stocks')), // Redirect to stocks page
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-stocks');
        // Handle the save logic (Example: store in DB)
        Stock::create($this->form->getState());

        session()->flash('success', 'Stock added successfully!');
        return redirect()->route('filament.admin.pages.stocks');
    }
}
