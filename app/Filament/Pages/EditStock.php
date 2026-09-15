<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;

class EditStock extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil';
    protected static string $view = 'filament.pages.edit-stock';

    public $stock;

    public function mount() // Pass ID manually
    {
        $this->stock = Stock::findOrFail(request()->id);
        // ✅ Fill form state with existing stock data
        $this->form->fill($this->stock->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Edit Stock Information')
                    ->schema([
                        TextInput::make('title')
                            ->label('Stock Title')
                            ->required()
                            ->maxLength(255)
                            ->default($this->stock['title']),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->nullable()
                            ->default($this->stock['description']),

                        FileUpload::make('image')
                            ->disk('public')
                            ->label('Stock Image')
                            ->image()
                            ->default($this->stock['image']),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->default($this->stock['quantity']),
                        Actions::make([
                            // ✅ updateStock Stock Button
                            Action::make('updateStock')
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
            ->statePath('stock'); // ✅ Binds directly to $this->stock
    }

    public function updateStock()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-stocks');
        $data = $this->form->getState();

        // ✅ Check if 'image' is set and handle the array format
        if (isset($data['image'])) {
            // If 'image' is an associative array, get the first value (actual file path)
            $newImage = is_array($data['image']) ? reset($data['image']) : $data['image'];

            // ✅ Ensure we delete the old image only if it's different
            if ($newImage !== $this->stock['image']) {
                Storage::disk('public')->delete($this->stock['image'] ?? '');
            }

            // ✅ Save the new image path correctly
            $data['image'] = $newImage;
        }

        // ✅ Update the stock record
        Stock::find($this->stock['id'])->update($data);
        session()->flash('success', 'Stock updated successfully!');
        return redirect()->route('filament.admin.pages.stocks');
    }

}
