<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Brand name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->placeholder('e.g. LEGO Deutschland GmbH'),
                        Textarea::make('ai_context')
                            ->label('AI context')
                            ->rows(6)
                            ->placeholder('Add brand-specific context for AI-assisted workflows.')
                            ->helperText('Used to give AI tools brand-specific instructions or background.')
                            ->columnSpanFull(),
                        TextInput::make('article_number_prefix')
                            ->label('Article number prefix')
                            ->maxLength(255)
                            ->placeholder('e.g. LEGO')
                            ->helperText('Used to prepend manufacturer SKUs.'),
                        TextInput::make('default_wg1')
                            ->label('Default Wg1 (Category 1)')
                            ->maxLength(255)
                            ->placeholder('e.g. Bausteine'),
                        TextInput::make('default_wg2')
                            ->label('Default Wg2 (Category 2)')
                            ->maxLength(255)
                            ->placeholder('e.g. LEGO Sets'),
                        TextInput::make('default_manufacturer_id')
                            ->label('Default manufacturer ID')
                            ->maxLength(255)
                            ->placeholder('e.g. LEGO001')
                            ->columnSpanFull(),
                    ]),
                Section::make('Pricing configuration')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('default_supplier_margin')
                            ->label('Our distributor margin (%)')
                            ->numeric()
                            ->required()
                            ->default(25)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->placeholder('25')
                            ->helperText('Markup on cost price to calculate VK1 (B2B wholesale price).'),
                        TextInput::make('minimum_shop_margin')
                            ->label('Minimum retailer margin (%)')
                            ->numeric()
                            ->required()
                            ->default(15)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->placeholder('15')
                            ->helperText('Minimum margin the B2B retailer earns (VK3 vs VK1).'),
                        Select::make('price_currency')
                            ->label('Price currency')
                            ->options([
                                'EUR' => 'EUR',
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                                'CHF' => 'CHF',
                            ])
                            ->default('EUR')
                            ->native(false)
                            ->required(),
                        TextInput::make('currency_factor')
                            ->label('Currency factor (→ CHF)')
                            ->numeric()
                            ->required()
                            ->default(1.1000)
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->placeholder('1.1000')
                            ->helperText('Multiplier to convert source currency to CHF (incl. exchange rate, shipping, fees).'),
                        Select::make('default_rounding_rule')
                            ->label('Default rounding rule')
                            ->options([
                                'none' => 'No rounding',
                                'end_with_90' => 'x.90 - End with .90',
                                'end_with_99' => 'x.99 - End with .99',
                                'whole_number' => 'Round to whole number',
                            ])
                            ->default('end_with_90')
                            ->native(false)
                            ->required(),
                    ]),
                Section::make('Default product flags')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Product is active in the system.'),
                        Checkbox::make('is_webshop')
                            ->label('Webshop')
                            ->default(false)
                            ->helperText('Product is available in webshop.'),
                        Checkbox::make('is_webshop_active')
                            ->label('Webshop active')
                            ->default(false)
                            ->helperText('Product actively promoted in webshop.'),
                    ]),
                Section::make('Timestamps')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?string $operation): bool => $operation === 'edit')
                    ->schema([
                        Placeholder::make('created_at_display')
                            ->label('Created at')
                            ->content(fn (?Brand $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?Brand $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}
