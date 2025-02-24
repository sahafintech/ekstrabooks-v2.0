@php $type = isset($type) ? $type : 'preview'; @endphp

@include('backend.user.purchase_order.template.default', ['type' => $type])