@php $type = isset($type) ? $type : 'preview'; @endphp

@include('backend.user.sales_return.template.default', ['type' => $type])