<?php

namespace App\Filament\Resources\DocumentConversations\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->columns(2)
                    ->schema([
                        TextInput::make('artnr')
                            ->label('Article Number')
                            ->maxLength(255),
                        TextInput::make('bestellnr')
                            ->label('Order Number')
                            ->maxLength(255),
                        TextInput::make('artean')
                            ->label('EAN')
                            ->maxLength(255),
                        TextInput::make('gtin2')
                            ->label('GTIN 2')
                            ->maxLength(255),
                        TextInput::make('hersteller_id')
                            ->label('Manufacturer ID')
                            ->maxLength(255),
                    ]),
                Section::make('Description')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bez1')
                            ->label('Product Name')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Textarea::make('kurztext')
                            ->label('Short Text')
                            ->columnSpanFull()
                            ->rows(2),
                        Textarea::make('langtext')
                            ->label('Long Text')
                            ->columnSpanFull()
                            ->rows(4),
                    ]),
                Section::make('Classification')
                    ->columns(2)
                    ->schema([
                        TextInput::make('wg1')
                            ->label('Product Group 1')
                            ->maxLength(255),
                        TextInput::make('wg2')
                            ->label('Product Group 2')
                            ->maxLength(255),
                    ]),
                Section::make('Pricing')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vk1')
                            ->label('Selling Price 1')
                            ->numeric(),
                        TextInput::make('vk2')
                            ->label('Selling Price 2')
                            ->numeric(),
                        TextInput::make('vk3')
                            ->label('Selling Price 3')
                            ->numeric(),
                        TextInput::make('ek')
                            ->label('Purchase Price')
                            ->numeric(),
                        TextInput::make('mwst')
                            ->label('VAT %')
                            ->numeric(),
                    ]),
                Section::make('Logistics')
                    ->columns(2)
                    ->schema([
                        TextInput::make('gewnetto')
                            ->label('Net Weight (g)')
                            ->numeric(),
                        TextInput::make('gewbrutto')
                            ->label('Gross Weight (g)')
                            ->numeric(),
                        TextInput::make('verkaufsmenge')
                            ->label('Sales Quantity')
                            ->numeric(),
                        TextInput::make('verkaufsmenge_staffel')
                            ->label('Quantity Tier')
                            ->numeric(),
                        TextInput::make('wbztage')
                            ->label('Delivery Days')
                            ->numeric(),
                    ]),
                Section::make('Origin & Customs')
                    ->columns(2)
                    ->schema([
                        TextInput::make('uspland')
                            ->label('Origin Country Code')
                            ->maxLength(255),
                        TextInput::make('ursprungsland')
                            ->label('Country of Origin')
                            ->maxLength(255),
                        TextInput::make('zolltarifnr')
                            ->label('Customs Tariff No.')
                            ->maxLength(255),
                        TextInput::make('zolltarifnr_ch')
                            ->label('Swiss Customs Tariff No.')
                            ->maxLength(255),
                        TextInput::make('zolltarifnr_bez')
                            ->label('Customs Tariff Desc.')
                            ->maxLength(255),
                    ]),
                Section::make('Flags')
                    ->columns(2)
                    ->schema([
                        Checkbox::make('aktiv')
                            ->label('Active'),
                        Checkbox::make('webshop')
                            ->label('Webshop'),
                        Checkbox::make('ws_aktiv')
                            ->label('Webshop Active'),
                        Checkbox::make('ws_abverkauf')
                            ->label('Clearance Sale'),
                        TextInput::make('ws_dateavailable')
                            ->label('Webshop Available Date')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('artnr')
            ->columns([
                TextColumn::make('artnr')
                    ->label('Article No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bez1')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('vk1')
                    ->label('Price')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('ek')
                    ->label('Cost')
                    ->numeric(2)
                    ->sortable(),
                IconColumn::make('aktiv')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('artean')
                    ->label('EAN')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
