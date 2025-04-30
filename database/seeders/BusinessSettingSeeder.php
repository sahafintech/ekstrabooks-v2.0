<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    protected $businessId;

    public function __construct($businessId)
    {
        $this->businessId = $businessId;
    }

    public function run(): void
    {
        DB::table('business_settings')->insert([
            // credit invoce
            [
                'name' => 'invoice_title',
                'value' => 'Credit Invoice',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_number',
                'value' => '1006',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_primary_color',
                'value' => '#FFF',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_primary_color',
                'value' => '#ffffff',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_text_color',
                'value' => '#000000',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'invoice_column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            // qoutation
            [
                'name' => 'quotation_title',
                'value' => 'Customer Quotation',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'quotation_number',
                'value' => '100001',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'quotation_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'quotation_column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            // general
            [
                'name' => 'timezone',
                'value' => 'Africa/Mogadishu',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'language',
                'value' => 'English---us',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'date_format',
                'value' => 'd/m/Y',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'time_format',
                'value' => '24',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'currency_position',
                'value' => 'left',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'thousand_sep',
                'value' => ',',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'decimal_sep',
                'value' => '.',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'decimal_places',
                'value' => '2',
                'business_id' => $this->businessId
            ],
            // cash invoice
            [
                'name' => 'receipt_title',
                'value' => 'Cash Invoice',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'receipt_number',
                'value' => '1010',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'receipt_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'receipt_column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            // Pos
            [
                'name' => 'pos_default_taxes',
                'value' => "",
                'business_id' => $this->businessId
            ],
            [
                'name' => 'pos_default_currency_change',
                'value' => '',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'pos_product_image',
                'value' => 0,
                'business_id' => $this->businessId
            ],
            // general
            [
                'name' => 'fiscal_year',
                'value' => 'January,December',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'start_day',
                'value' => '1',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'weekends',
                'value' => '["Thursday","Friday"]',
                'business_id' => $this->businessId
            ],
            // sales return
            [
                'name' => 'sales_return_title',
                'value' => 'Sales Return',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'sales_return_number',
                'value' => '1014',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'sales_return_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'sales_return-column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'journal_number',
                'value' => '10000038',
                'business_id' => $this->businessId
            ],
            // purchase
            [
                'name' => 'purchase_title',
                'value' => 'Bill Invoice',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_number',
                'value' => '1001',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'bill_number',
                'value' => '1001',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            // purchase order
            [
                'name' => 'purchase_order_title',
                'value' => 'Purchase Order',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_order_number',
                'value' => '1001',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_order_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_order_column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            // purchase return
            [
                'name' => 'purchase_return_title',
                'value' => 'Purchase Return',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_return_number',
                'value' => '1001',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_return_footer',
                'value' => null,
                'business_id' => $this->businessId
            ],
            [
                'name' => 'purchase_return-column',
                'value' => '{"name":{"label":"Name","status":"1"},"description":{"status":"1"},"quantity":{"label":"Quantity","status":"1"},"price":{"label":"Price","status":"1"},"amount":{"label":"Amount","status":"1"}}',
                'business_id' => $this->businessId
            ],
            [
                'name' => 'queue_number',
                'value' => '1',
                'business_id' => $this->businessId
            ]
        ]);
    }
}