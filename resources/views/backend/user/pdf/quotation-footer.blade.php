@php
    $primaryColor = get_business_option('invoice_primary_color', '#6d0e47');
    $textColor = get_business_option('invoice_text_color', '#ffffff');
    $businessEmail = $quotation->business->business_email ?? $quotation->business->email ?? '';
    $footerText = collect([
        $quotation->business->phone ?? null,
        $businessEmail,
        $quotation->business->website ?? null,
    ])->filter()->join(' | ');
@endphp

<div style="position: absolute; right: 7mm; bottom: 6mm; left: 7mm; box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
    <div style="box-sizing: border-box; width: 100%; min-height: 9mm; padding: 2.5mm 4mm; background-color: {{ $primaryColor }}; color: {{ $textColor }}; text-align: center; font-size: 9px; line-height: 1.2; font-weight: 600;">
        {{ $footerText }}
    </div>
</div>
