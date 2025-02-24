@php $type = isset($type) ? $type : 'preview'; @endphp

@include('backend.user.cash_purchase.template.default', ['type' => $type])