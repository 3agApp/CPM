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
                            ->label('Supplier Ref.')
                            ->maxLength(255),
                        TextInput::make('artean')
                            ->label('EAN')
                            ->maxLength(255),
                        TextInput::make('hersteller_id')
                            ->label('Manufacturer ID')
                            ->maxLength(255),
                        TextInput::make('brand_name')
                            ->label('Brand')
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
                Section::make('Source Pricing (EUR)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ek_eur')
                            ->label('Supplier Price (EUR)')
                            ->numeric()
                            ->prefix('€'),
                        TextInput::make('uvp_eur')
                            ->label('Mfr. RRP (EUR)')
                            ->numeric()
                            ->prefix('€'),
                    ]),
                Section::make('Calculated Pricing (CHF)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ek')
                            ->label('Our Cost (CHF)')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('vk1')
                            ->label('VK1 – B2B Wholesale')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('vk2')
                            ->label('VK2 – Education')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('vk3')
                            ->label('VK3 – Consumer RRP')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('mwst')
                            ->label('VAT %')
                            ->numeric()
                            ->suffix('%'),
                    ]),
                Section::make('Price Comparison & Margins')
                    ->columns(3)
                    ->schema([
                        TextInput::make('vk_de_chf')
                            ->label('German RRP (CHF)')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('price_diff_percent')
                            ->label('Price Difference')
                            ->numeric()
                            ->suffix('%'),
                        TextInput::make('margin_amount')
                            ->label('Our Margin')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('margin_percent')
                            ->label('Our Margin %')
                            ->numeric()
                            ->suffix('%'),
                        TextInput::make('shop_margin_amount')
                            ->label('Retailer Margin')
                            ->numeric()
                            ->prefix('CHF'),
                        TextInput::make('shop_margin_percent')
                            ->label('Retailer Margin %')
                            ->numeric()
                            ->suffix('%'),
                    ]),
                Section::make('Logistics')
                    ->columns(2)
                    ->collapsed()
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
                    ->collapsed()
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
                    ->collapsed()
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
                    ->limit(30),
                TextColumn::make('ek_eur')
                    ->label('EK (EUR)')
                    ->numeric(2)
                    ->prefix('€')
                    ->sortable(),
                TextColumn::make('ek')
                    ->label('EK (CHF)')
                    ->numeric(2)
                    ->prefix('CHF ')
                    ->sortable(),
                TextColumn::make('vk1')
                    ->label('VK1 B2B')
                    ->numeric(2)
                    ->prefix('CHF ')
                    ->sortable(),
                TextColumn::make('vk3')
                    ->label('VK3 RRP')
                    ->numeric(2)
                    ->prefix('CHF ')
                    ->sortable(),
                TextColumn::make('vk_de_chf')
                    ->label('DE RRP')
                    ->numeric(2)
                    ->prefix('CHF ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price_diff_percent')
                    ->label('Diff %')
                    ->numeric(1)
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        abs((float) $state) <= 20 => 'success',
                        abs((float) $state) <= 30 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('margin_percent')
                    ->label('Our Margin')
                    ->numeric(1)
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('aktiv')
                    ->label('Active')
                    ->boolean(),
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
