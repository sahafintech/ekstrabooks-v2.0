@php $type = isset($type) ? $type : 'preview'; @endphp

@include('backend.user.purchase_return.template.default', ['type' => $type])