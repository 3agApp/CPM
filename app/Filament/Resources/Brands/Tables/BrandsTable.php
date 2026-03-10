<?php

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Brand name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('article_number_prefix')
                    ->label('Prefix')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('default_manufacturer_id')
                    ->label('Manufacturer ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price_currency')
                    ->label('Currency')
                    ->badge(),
                TextColumn::make('default_supplier_margin')
                    ->label('Brand margin')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No brands found')
            ->emptyStateDescription('Create your first brand to store default pricing and product settings.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->accessSelectedRecords(),
                ]),
            ]);
    }
}
