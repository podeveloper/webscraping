<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapResource\Pages;
use App\Filament\Resources\ScrapResource\RelationManagers;
use App\Models\Scrap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ScrapResource extends Resource
{
    protected static ?string $model = Scrap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category')
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('source')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([10, 25, 50, 100])
            ->filters([
                Tables\Filters\Filter::make('category')
                    ->form([
                        Forms\Components\Select::make('category')
                            ->options(Scrap::distinct()->pluck('category','category')->toArray()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $category = $data['category'];
                        return $category ? $query->where('category', '=', $category) : $query;
                    }),
                Tables\Filters\Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('name'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $name = $data['name'];
                        return $name ? $query->where('name', 'like', '%' . $name . '%') : $query;
                    }),
                Tables\Filters\Filter::make('url')
                    ->form([
                        Forms\Components\TextInput::make('url'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $url = $data['url'];
                        return $url ? $query->where('url', 'like', '%' . $url . '%') : $query;
                    }),
                Tables\Filters\Filter::make('source')
                    ->form([
                        Forms\Components\TextInput::make('source'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $source = $data['source'];
                        return $source ? $query->where('source', 'like', '%' . $source . '%') : $query;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageScraps::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Scrap::count();
    }
}
