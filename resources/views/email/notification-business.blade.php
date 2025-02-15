@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ isset($businessName) ? $businessName : request()->activeBusiness->name }}
@endcomponent
@endslot

{{-- Body --}}
{!! xss_clean($message) !!}

{{ _lang('Regards') }},<br>
{{ isset($businessName) ? $businessName : request()->activeBusiness->name }}

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ isset($businessName) ? $businessName : request()->activeBusiness->name }}. _lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
