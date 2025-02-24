@php $type = isset($type) ? $type : 'preview'; @endphp
<style type="text/css">
	{{ $quotation->invoice_template->custom_css }}
</style>
<!-- Default Invoice template -->
<div id="invoice" class="{{ $type }}">
	<div class="custom-invoice">
		@php
		$replace = array(
			'$quotation_title'       => $quotation->title,
			'$business_name'         => $quotation->business->name,
			'$business_address'      => $quotation->business->address,
			'$business_phone'        => $quotation->business->phone,
			'$business_email'        => $quotation->business->email,
			'$business_country'      => $quotation->business->country,
			'$business_vat_id'       => $quotation->business->vat_id,
			'$business_reg_no'       => $quotation->business->reg_no,
			'$customer_name'   		 => $quotation->customer->name,
			'$customer_email'        => $quotation->customer->email,
			'$customer_mobile'       => $quotation->customer->mobile,
			'$customer_company_name' => $quotation->customer->company_name,
			'$customer_vat_id'       => $quotation->customer->vat_id,
			'$customer_reg_no'       => $quotation->customer->reg_no,
			'$customer_city'         => $quotation->customer->city,
			'$customer_state'        => $quotation->customer->state,
			'$customer_zip'          => $quotation->customer->zip,
			'$customer_address'      => $quotation->customer->address,
			'$customer_country'      => $quotation->customer->country,


			'$quotation_number'         => $quotation->quotation_number,
			'$po_so_number'         	=> $quotation->po_so_number,
			'$quotation_date'           => $quotation->quotation_date,
			'$quotation_expired_date'   => $quotation->expired_date,
			'$status'				  	=> quotation_status($quotation),
			'$sub_total'			  	=> formatAmount($quotation->sub_total, currency_symbol($quotation->business->currency), $quotation->business_id),
			//'$taxes_loop'			  	=> '',
			'$discount'				  	=> formatAmount($quotation->discount, currency_symbol($quotation->business->currency), $quotation->business_id),
			'$grand_total'			  	=> formatAmount($quotation->grand_total, currency_symbol($quotation->business->currency), $quotation->business_id),
			'$quotation_note'			=> $quotation->note,
			'$quotation_footer_details' => $quotation->footer,
		);


		$invoice_content = strtr($quotation->invoice_template->body, $replace);
		$quotationColumns = json_decode(get_business_option('quotation_column', null, $quotation->business_id));

		$invoice_content = str_replace('<!--$invoice_items_header-->', view('backend.user.quotation.template.components.invoice-items-header', compact('quotation', 'quotationColumns'))->render(), $invoice_content);
		$invoice_content = str_replace('<!--$invoice_items-->', view('backend.user.quotation.template.components.invoice-items', compact('quotation', 'quotationColumns'))->render(), $invoice_content);
		$invoice_content = str_replace('<!--$invoice_summary-->', view('backend.user.quotation.template.components.invoice-summary', compact('quotation'))->render(), $invoice_content);
		$invoice_content = str_replace('$tax_loop', view('backend.user.quotation.template.components.invoice-taxes', compact('quotation'))->render(), $invoice_content);


		$logo = '<img src="' . ($type == 'pdf' ? public_path('uploads/media/' . $quotation->business->logo) : asset('public/uploads/media/' . $quotation->business->logo)).'" class="element-logo wp-100">';

		$invoice_content = str_replace('<!--$company_logo-->', $logo, $invoice_content);

		@endphp

		{!! xss_clean($invoice_content) !!}
	</div>
</div>