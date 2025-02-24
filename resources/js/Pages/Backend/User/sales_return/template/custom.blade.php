@php $type = isset($type) ? $type : 'preview'; @endphp
<style type="text/css">
	{{ $invoice->invoice_template->custom_css }}
</style>
<!-- Default Invoice template -->
<div id="invoice" class="{{ $type }}">
	<div class="custom-invoice">
		@php
		$replace = array(
			'$invoice_title'         => $invoice->title,
			'$business_name'         => $invoice->business->name,
			'$business_address'      => $invoice->business->address,
			'$business_phone'        => $invoice->business->phone,
			'$business_email'        => $invoice->business->email,
			'$business_country'      => $invoice->business->country,
			'$business_vat_id'       => $invoice->business->vat_id,
			'$business_reg_no'       => $invoice->business->reg_no,
			'$customer_name'   		 => $invoice->customer->name,
			'$customer_email'        => $invoice->customer->email,
			'$customer_mobile'       => $invoice->customer->mobile,
			'$customer_company_name' => $invoice->customer->company_name,
			'$customer_vat_id'       => $invoice->customer->vat_id,
			'$customer_reg_no'       => $invoice->customer->reg_no,
			'$customer_city'         => $invoice->customer->city,
			'$customer_state'        => $invoice->customer->state,
			'$customer_zip'          => $invoice->customer->zip,
			'$customer_address'      => $invoice->customer->address,
			'$customer_country'      => $invoice->customer->country,


			'$invoice_number'         => $invoice->invoice_number,
			'$sales_order_no'         => $invoice->order_number,
			'$invoice_date'           => $invoice->invoice_date,
			'$invoice_due_date'       => $invoice->due_date,
			'$status'				  => invoice_status($invoice),
			'$sub_total'			  => formatAmount($invoice->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id),
			//'$tax_loop'		  => '',
			'$discount'				  => formatAmount($invoice->discount, currency_symbol($invoice->business->currency), $invoice->business_id),
			'$grand_total'			  => formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id),
			'$total_paid'			  => formatAmount($invoice->paid, currency_symbol($invoice->business->currency), $invoice->business_id),
			'$amount_due'			  => formatAmount($invoice->grand_total - $invoice->paid, currency_symbol($invoice->business->currency), $invoice->business_id),
			'$invoice_note'			  => $invoice->note,
			'$invoice_footer_details' => $invoice->footer,
		);


		$invoice_content = strtr($invoice->invoice_template->body, $replace);
		$invoiceColumns = json_decode(get_business_option('invoice_column', null, $invoice->business_id));

		$invoice_content = str_replace('<!--$invoice_items_header-->', view('backend.user.invoice.template.components.invoice-items-header', compact('invoice', 'invoiceColumns'))->render(), $invoice_content);
		$invoice_content = str_replace('<!--$invoice_items-->', view('backend.user.invoice.template.components.invoice-items', compact('invoice', 'invoiceColumns'))->render(), $invoice_content);
		$invoice_content = str_replace('<!--$invoice_summary-->', view('backend.user.invoice.template.components.invoice-summary', compact('invoice'))->render(), $invoice_content);
		$invoice_content = str_replace('<!--$invoice_payment_history-->', view('backend.user.invoice.template.components.invoice-payment-history', compact('invoice'))->render(), $invoice_content);
		$invoice_content = str_replace('$tax_loop', view('backend.user.invoice.template.components.invoice-taxes', compact('invoice'))->render(), $invoice_content);
			
		$logo = '<img src="' . ($type == 'pdf' ? public_path('uploads/media/' . $invoice->business->logo) : asset('public/uploads/media/' . $invoice->business->logo)).'" class="element-logo wp-100">';

		$invoice_content = str_replace('<!--$company_logo-->', $logo, $invoice_content);

		@endphp

		{!! xss_clean($invoice_content) !!}
	</div>
</div>