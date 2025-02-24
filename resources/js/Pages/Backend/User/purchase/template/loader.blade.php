@php $type = isset($type) ? $type : 'preview'; @endphp

@include('backend.user.purchase.template.default', ['type' => $type])