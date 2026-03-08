<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Suppliers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Supplier Name')
                            ->placeholder('e.g., LEGO Deutschland GmbH')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('article_number_prefix')
                            ->label('Article Number Prefix')
                            ->placeholder('LEGO')
                            ->helperText('Used to prepend manufacturer SKUs')
                            ->maxLength(255),
                        TextInput::make('default_wg1')
                            ->label('Default Wg1 (Category 1)')
                            ->placeholder('Bausteine')
                            ->maxLength(255),
                        TextInput::make('default_wg2')
                            ->label('Default Wg2 (Category 2)')
                            ->placeholder('LEGO Sets')
                            ->maxLength(255),
                        TextInput::make('default_manufacturer_id')
                            ->label('Default Manufacturer ID')
                            ->placeholder('LEGO001')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Pricing Configuration')
                    ->schema([
                        TextInput::make('default_supplier_margin')
                            ->label('Default Supplier Margin (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(25)
                            ->placeholder('25')
                            ->helperText('Used for HEK calculation')
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                        TextInput::make('minimum_shop_margin')
                            ->label('Minimum Shop Margin (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(15)
                            ->placeholder('15')
                            ->helperText('Minimum required shop margin')
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                        Select::make('price_currency')
                            ->label('Price Currency')
                            ->options(self::getCurrencyOptions())
                            ->default('EUR')
                            ->required(),
                        Select::make('default_rounding_rule')
                            ->label('Default Rounding Rule')
                            ->options(self::getRoundingRuleOptions())
                            ->default('end_with_90')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Default Product Flags')
                    ->schema([
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->helperText('Product is active in the system')
                            ->default(true),
                        Checkbox::make('is_webshop')
                            ->label('Webshop')
                            ->helperText('Product is available in webshop')
                            ->default(false),
                        Checkbox::make('is_webshop_active')
                            ->label('Webshop Active')
                            ->helperText('Product actively promoted in webshop')
                            ->default(false),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('article_number_prefix')
                    ->label('Prefix')
                    ->searchable(),
                TextColumn::make('price_currency')
                    ->badge(),
                TextColumn::make('default_supplier_margin')
                    ->label('Supplier Margin')
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
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getCurrencyOptions(): array
    {
        return [
            'EUR' => 'EUR',
            'USD' => 'USD',
            'GBP' => 'GBP',
            'CHF' => 'CHF',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getRoundingRuleOptions(): array
    {
        return [
            'none' => 'No rounding',
            'end_with_90' => 'x.90 - End with .90',
            'end_with_99' => 'x.99 - End with .99',
            'whole_number' => 'Round to whole number',
        ];
    }
}
