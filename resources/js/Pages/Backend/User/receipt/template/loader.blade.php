@php $type = isset($type) ? $type : 'preview'; @endphp

@if($receipt->template_type == 0)
    @include('backend.user.receipt.template.'.$receipt->template, ['type' => $type])
@elseif($receipt->template_type == 1 && $receipt->receipt_template->name != null)
    @include('backend.user.receipt.template.custom', ['type' => $type])
@else
    @include('backend.user.receipt.template.default', ['type' => $type])
@endif