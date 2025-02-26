@php $type = isset($type) ? $type : 'preview'; @endphp

@if($invoice->template_type == 0)
    @include('backend.user.invoice.template.'.$invoice->template, ['type' => $type])
@elseif($invoice->template_type == 1 && $invoice->invoice_template->name != null)
    @include('backend.user.invoice.template.custom', ['type' => $type])
@else
    @include('backend.user.invoice.template.default', ['type' => $type])
@endif