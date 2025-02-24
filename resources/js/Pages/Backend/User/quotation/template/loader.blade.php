@php $type = isset($type) ? $type : 'preview'; @endphp

@if($quotation->template_type == 0)
    @include('backend.user.quotation.template.'.$quotation->template, ['type' => $type])
@elseif($quotation->template_type == 1 && $quotation->invoice_template->name != null)
    @include('backend.user.quotation.template.custom', ['type' => $type])
@else
    @include('backend.user.quotation.template.default', ['type' => $type])
@endif