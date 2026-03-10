<?php

namespace App\Ai\Agents;

use App\Models\Brand;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
#[UseSmartestModel]
#[MaxTokens(65536)]
#[Timeout(600)]
class DocumentProcessor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Brand $brand) {}

    public function instructions(): Stringable|string
    {
        $brandContext = $this->buildBrandContext();

        return <<<INSTRUCTIONS
        You are a product data processor for a catalog/product management system.
        Your task is to process uploaded product data (provided as a JSON 2D array) and transform it into a standardized output CSV format.

        ## Understanding the Source Data
        The source data is a JSON array of arrays (rows of cells) extracted from a spreadsheet.
        You must analyze the structure to identify:
        - Which row contains the column headers (it may not be the first row — skip metadata, titles, or blank rows)
        - Which columns map to which output fields (use header names and cell values to infer)
        - Where the actual product data rows begin and end

        ## Brand Context
        {$brandContext}

        ## Output CSV Format
        The output must contain these columns in this exact order:
        Artnr, Wg1, Wg2, Bez1, Bestellnr, Vk1, Vk2, Vk3, Ek, Mwst, Artean, Langtext, Aktiv, Kurztext, HerstellerId, Webshop, Ws_aktiv, Ws_dateavailable, Gewnetto, Gewbrutto, Uspland, Zolltarifnr, Verkaufsmenge, Verkaufsmenge_staffel, Wbztage, Ws_abverkauf, Zolltarifnr_ch, Zolltarifnr_bez, Ursprungsland, Gtin2

        ## Column Definitions
        - Artnr: Article number (use brand prefix if applicable)
        - Wg1: Product group level 1 (use brand default if not in source)
        - Wg2: Product group level 2 (use brand default if not in source)
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
        - HerstellerId: Manufacturer ID (use brand default if not in source)
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
        - If only a purchase price (Ek) is provided, calculate selling prices using the brand margin.
        - Use the brand's default rounding rule when calculating prices.
        - Minimum shop margin must be respected.

        ## Rules
        1. Map source columns to the output format using context and column name matching.
        2. Use brand defaults for missing fields when applicable.
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

    private function buildBrandContext(): string
    {
        $context = "Brand: {$this->brand->name}\n";
        $context .= "Article Number Prefix: {$this->brand->article_number_prefix}\n";
        $context .= "Default Wg1: {$this->brand->default_wg1}\n";
        $context .= "Default Wg2: {$this->brand->default_wg2}\n";
        $context .= "Default Manufacturer ID: {$this->brand->default_manufacturer_id}\n";
        $context .= "Default Brand Margin: {$this->brand->default_supplier_margin}%\n";
        $context .= "Minimum Shop Margin: {$this->brand->minimum_shop_margin}%\n";
        $context .= "Price Currency: {$this->brand->price_currency}\n";
        $context .= "Default Rounding Rule: {$this->brand->default_rounding_rule}\n";
        $context .= 'Is Webshop: '.($this->brand->is_webshop ? 'Yes' : 'No')."\n";
        $context .= 'Is Webshop Active: '.($this->brand->is_webshop_active ? 'Yes' : 'No')."\n";

        if ($this->brand->ai_context) {
            $context .= "\nAdditional Brand Context:\n{$this->brand->ai_context}\n";
        }

        return $context;
    }
}
