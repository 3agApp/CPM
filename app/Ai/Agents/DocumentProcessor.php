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
        You are a product data processor for a Swiss distributor's catalog/pricing management system.
        The user is a Swiss distributor who buys products from suppliers (manufacturers/brands) and sells them
        to B2B retailers and B2C end consumers in Switzerland.
        Your task is to process supplier price list data and calculate the distributor's Swiss market pricing.

        ## Understanding the Source Data
        The source data is a supplier price list (spreadsheet file attached to this prompt).
        You must analyze the structure to identify:
        - Which row contains the column headers (it may not be the first row — skip metadata, titles, or blank rows)
        - Which columns map to which output fields (use header names and cell values to infer)
        - Where the actual product data rows begin and end

        Supplier price lists are typically in EUR and may include columns like:
        - Art. Nr. / Artikelnr / Bestell-Nr → supplier's article/reference number
        - Brand / Marke / Hersteller → brand/manufacturer name
        - Artikelname / Bezeichnung / Bez1 → product name
        - EK / EK SP3 / Einkaufspreis / Händlereinkaufspreis → supplier's price to us in EUR (this is ek_eur)
        - UVP / UVP Brutto / RRP / Endverbraucherpreis → manufacturer's recommended retail price in EUR (this is uvp_eur)
        - EAN / GTIN / Barcode → EAN barcode
        - VE / Verpackungseinheit → packaging unit
        - Gewicht / Weight → weight
        - Herkunft / Origin → country of origin
        - Zolltarifnr / HS Code → customs tariff number

        ## Brand Context
        {$brandContext}

        ## Swiss Pricing Calculation Rules
        All prices in the output must be in CHF. Follow these steps for each product:

        ### Step 1: Source Prices (EUR)
        - ek_eur: The price the supplier charges us in EUR (from source data)
        - uvp_eur: The manufacturer's recommended retail price in EUR (from source data, if available)

        ### Step 2: Convert to CHF
        - ek = ek_eur × currency_factor (our cost price in CHF — what we pay)
        - vk_de_chf = uvp_eur × currency_factor (German RRP in CHF, used only for price comparison)

        ### Step 3: Calculate Our Selling Prices (CHF)
        - vk1 = ek × (1 + supplier_margin / 100) → our B2B wholesale price to retailers (HEK Final)
        - vk3 = vk1 × (1 + (100 / (100 - minimum_shop_margin) - 1)) → end-consumer RRP in Switzerland
          Ensure: ((vk3 - vk1) / vk3) × 100 >= minimum_shop_margin (the retailer's margin)
        - vk2 = vk3 × 0.85 → education/special price (15% discount on RRP)

        ### Step 4: Apply Rounding
        Apply the brand's rounding rule to vk1, vk2, vk3, and ek:
        - "0.05": Round to nearest 0.05 CHF (e.g., 12.43 → 12.45)
        - "0.10": Round to nearest 0.10 CHF
        - "0.50": Round to nearest 0.50 CHF
        - "1.00": Round to nearest 1.00 CHF
        - "none" or empty: No rounding, use 2 decimal places

        ### Step 5: Calculate Margins
        - margin_amount = vk1 - ek (our distributor margin in CHF — what we earn per unit)
        - margin_percent = ((vk1 - ek) / ek) × 100 (our distributor margin percentage)
        - shop_margin_amount = vk3 - vk1 (retailer's margin in CHF — what the B2B retailer earns)
        - shop_margin_percent = ((vk3 - vk1) / vk3) × 100 (retailer's margin percentage)

        ### Step 6: Price Comparison
        - price_diff_percent = ((vk3 - vk_de_chf) / vk_de_chf) × 100
          This shows how much the Swiss price differs from the German price.
          Negative means Swiss price is lower. Positive means Swiss price is higher.

        ## Output Fields
        For each product, return these fields:

        ### Identifiers
        - artnr: Our internal article number (use brand prefix + source article number)
        - bestellnr: Supplier's reference/SKU number (the code we use to order from them)
        - artean: EAN/barcode
        - hersteller_id: Manufacturer ID (use brand default if not in source)
        - brand_name: Brand/manufacturer name from source data

        ### Description
        - bez1: Short product name
        - langtext: Long description (HTML allowed, preserve from source)
        - kurztext: Short text/subtitle

        ### Classification
        - wg1: Product group level 1 (use brand default if not in source)
        - wg2: Product group level 2 (use brand default if not in source)

        ### Source Pricing (EUR)
        - ek_eur: What the supplier charges us in EUR
        - uvp_eur: Manufacturer's recommended retail price in EUR

        ### Calculated Pricing (CHF)
        - ek: Our cost price in CHF (ek_eur × currency_factor)
        - vk1: Our B2B wholesale price in CHF (what retailers pay us)
        - vk2: Education/special price in CHF
        - vk3: End-consumer RRP in Switzerland in CHF
        - mwst: Swiss VAT rate (always 8.1)

        ### Price Comparison
        - vk_de_chf: German RRP converted to CHF (for comparison only)
        - price_diff_percent: How much Swiss RRP differs from German price (%)

        ### Margins
        - margin_amount: Our distributor margin in CHF (vk1 - ek)
        - margin_percent: Our distributor margin percentage
        - shop_margin_amount: Retailer's margin in CHF (vk3 - vk1)
        - shop_margin_percent: Retailer's margin percentage

        ### Logistics
        - gewnetto: Net weight in grams
        - gewbrutto: Gross weight in grams
        - verkaufsmenge: Sales quantity (default 1)
        - verkaufsmenge_staffel: Sales quantity tier
        - wbztage: Delivery lead time in days

        ### Origin & Customs
        - uspland: Origin country code
        - ursprungsland: Country of origin name
        - zolltarifnr: Customs tariff number (EU)
        - zolltarifnr_ch: Swiss customs tariff number
        - zolltarifnr_bez: Customs tariff description

        ### Flags
        - aktiv: Active product (default true)
        - webshop: Show in webshop (use brand default)
        - ws_aktiv: Webshop active (use brand default)
        - ws_dateavailable: Date available for webshop (DD.MM.YYYY)
        - ws_abverkauf: Clearance sale flag (default false)

        ## Rules
        1. Map source columns to the output format using context and column name matching.
        2. Use brand defaults for missing fields when applicable.
        3. ALL prices in the output MUST be calculated — never leave pricing fields empty.
        4. If ek_eur cannot be determined from the source data, set "needs_clarification" to true.
        5. If uvp_eur is not available, set vk_de_chf and price_diff_percent to 0.
        6. Set "needs_clarification" to true ONLY if critical data (article number or purchase price) is missing.
        7. If you need clarification, provide a clear question in "question" and set "products" to an empty array.
        8. If you do NOT need clarification, set "question" to an empty string and provide all products.
        9. Process ALL rows from the source document.
        10. Preserve HTML in long text fields.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'needs_clarification' => $schema->boolean()->required(),
            'question' => $schema->string()->required(),
            'products' => $schema->array(
                $schema->object([
                    'artnr' => $schema->string(),
                    'bestellnr' => $schema->string(),
                    'artean' => $schema->string(),
                    'hersteller_id' => $schema->string(),
                    'brand_name' => $schema->string(),
                    'bez1' => $schema->string(),
                    'langtext' => $schema->string(),
                    'kurztext' => $schema->string(),
                    'wg1' => $schema->string(),
                    'wg2' => $schema->string(),
                    'ek_eur' => $schema->number(),
                    'uvp_eur' => $schema->number(),
                    'ek' => $schema->number(),
                    'vk1' => $schema->number(),
                    'vk2' => $schema->number(),
                    'vk3' => $schema->number(),
                    'mwst' => $schema->number(),
                    'vk_de_chf' => $schema->number(),
                    'price_diff_percent' => $schema->number(),
                    'margin_amount' => $schema->number(),
                    'margin_percent' => $schema->number(),
                    'shop_margin_amount' => $schema->number(),
                    'shop_margin_percent' => $schema->number(),
                    'gewnetto' => $schema->number(),
                    'gewbrutto' => $schema->number(),
                    'verkaufsmenge' => $schema->integer(),
                    'verkaufsmenge_staffel' => $schema->integer(),
                    'wbztage' => $schema->integer(),
                    'uspland' => $schema->string(),
                    'ursprungsland' => $schema->string(),
                    'zolltarifnr' => $schema->string(),
                    'zolltarifnr_ch' => $schema->string(),
                    'zolltarifnr_bez' => $schema->string(),
                    'aktiv' => $schema->boolean(),
                    'webshop' => $schema->boolean(),
                    'ws_aktiv' => $schema->boolean(),
                    'ws_dateavailable' => $schema->string(),
                    'ws_abverkauf' => $schema->boolean(),
                ])
            )->required(),
        ];
    }

    private function buildBrandContext(): string
    {
        $context = "Brand: {$this->brand->name}\n";
        $context .= "Article Number Prefix: {$this->brand->article_number_prefix}\n";
        $context .= "Default Wg1: {$this->brand->default_wg1}\n";
        $context .= "Default Wg2: {$this->brand->default_wg2}\n";
        $context .= "Default Manufacturer ID: {$this->brand->default_manufacturer_id}\n";
        $context .= "Supplier Margin (retailer markup %): {$this->brand->default_supplier_margin}%\n";
        $context .= "Minimum Shop Margin: {$this->brand->minimum_shop_margin}%\n";
        $context .= "Source Price Currency: {$this->brand->price_currency}\n";
        $context .= "Currency Factor (EUR to CHF): {$this->brand->currency_factor}\n";
        $context .= "Default Rounding Rule: {$this->brand->default_rounding_rule}\n";
        $context .= 'Is Webshop: '.($this->brand->is_webshop ? 'Yes' : 'No')."\n";
        $context .= 'Is Webshop Active: '.($this->brand->is_webshop_active ? 'Yes' : 'No')."\n";

        if ($this->brand->ai_context) {
            $context .= "\nAdditional Brand Context:\n{$this->brand->ai_context}\n";
        }

        return $context;
    }
}
