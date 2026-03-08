<?php

namespace App\Ai\Agents;

use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Gemini)]
#[UseSmartestModel]
#[MaxTokens(16384)]
#[Temperature(0.2)]
#[Timeout(300)]
class DocumentProcessor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Supplier $supplier) {}

    public function instructions(): Stringable|string
    {
        $supplierContext = $this->buildSupplierContext();

        return <<<INSTRUCTIONS
        You are a product data processor for a catalog/product management system.
        Your task is to process uploaded product documents (CSV/XLSX) and transform them into a standardized output CSV format.

        ## Supplier Context
        {$supplierContext}

        ## Output CSV Format
        The output must contain these columns in this exact order:
        Artnr, Wg1, Wg2, Bez1, Bestellnr, Vk1, Vk2, Vk3, Ek, Mwst, Artean, Langtext, Aktiv, Kurztext, HerstellerId, Webshop, Ws_aktiv, Ws_dateavailable, Gewnetto, Gewbrutto, Uspland, Zolltarifnr, Verkaufsmenge, Verkaufsmenge_staffel, Wbztage, Ws_abverkauf, Zolltarifnr_ch, Zolltarifnr_bez, Ursprungsland, Gtin2

        ## Column Definitions
        - Artnr: Article number (use supplier prefix if applicable)
        - Wg1: Product group level 1 (use supplier default if not in source)
        - Wg2: Product group level 2 (use supplier default if not in source)
        - Bez1: Short product name/description
        - Bestellnr: Order number
        - Vk1: Selling price 1 (retail)
        - Vk2: Selling price 2
        - Vk3: Selling price 3
        - Ek: Purchase/cost price
        - Mwst: VAT rate (e.g., 8.1)
        - Artean: EAN/barcode number
        - Langtext: Long description (HTML allowed)
        - Aktiv: Active flag (TRUE/FALSE)
        - Kurztext: Short text description
        - HerstellerId: Manufacturer ID (use supplier default if not in source)
        - Webshop: Webshop flag (TRUE/FALSE)
        - Ws_aktiv: Webshop active flag (TRUE/FALSE)
        - Ws_dateavailable: Date available for webshop (DD.MM.YYYY)
        - Gewnetto: Net weight in grams
        - Gewbrutto: Gross weight in grams
        - Uspland: Origin country code
        - Zolltarifnr: Customs tariff number
        - Verkaufsmenge: Sales quantity
        - Verkaufsmenge_staffel: Sales quantity tier
        - Wbztage: Delivery days
        - Ws_abverkauf: Clearance sale flag (TRUE/FALSE)
        - Zolltarifnr_ch: Swiss customs tariff number
        - Zolltarifnr_bez: Customs tariff description
        - Ursprungsland: Country of origin
        - Gtin2: Secondary GTIN/barcode

        ## Price Calculation Rules
        - If only a purchase price (Ek) is provided, calculate selling prices using the supplier margin.
        - Use the supplier's default rounding rule when calculating prices.
        - Minimum shop margin must be respected.

        ## Rules
        1. Map source columns to the output format using context and column name matching.
        2. Use supplier defaults for missing fields when applicable.
        3. Set "needs_clarification" to true ONLY if critical data is missing and cannot be inferred.
        4. If you need clarification, provide a clear question in "question" and set "csv_output" to an empty string.
        5. If you do NOT need clarification, set "question" to an empty string and provide the full CSV in "csv_output".
        6. Process ALL rows from the source document.
        7. Preserve HTML in long text fields.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'needs_clarification' => $schema->boolean()->required(),
            'question' => $schema->string()->required(),
            'csv_output' => $schema->string()->required(),
        ];
    }

    private function buildSupplierContext(): string
    {
        $context = "Supplier: {$this->supplier->name}\n";
        $context .= "Article Number Prefix: {$this->supplier->article_number_prefix}\n";
        $context .= "Default Wg1: {$this->supplier->default_wg1}\n";
        $context .= "Default Wg2: {$this->supplier->default_wg2}\n";
        $context .= "Default Manufacturer ID: {$this->supplier->default_manufacturer_id}\n";
        $context .= "Default Supplier Margin: {$this->supplier->default_supplier_margin}%\n";
        $context .= "Minimum Shop Margin: {$this->supplier->minimum_shop_margin}%\n";
        $context .= "Price Currency: {$this->supplier->price_currency}\n";
        $context .= "Default Rounding Rule: {$this->supplier->default_rounding_rule}\n";
        $context .= 'Is Webshop: '.($this->supplier->is_webshop ? 'Yes' : 'No')."\n";
        $context .= 'Is Webshop Active: '.($this->supplier->is_webshop_active ? 'Yes' : 'No')."\n";

        if ($this->supplier->ai_context) {
            $context .= "\nAdditional Supplier Context:\n{$this->supplier->ai_context}\n";
        }

        return $context;
    }
}
