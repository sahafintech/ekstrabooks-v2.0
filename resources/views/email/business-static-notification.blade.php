@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ request()->activeBusiness->name }}
@endcomponent
@endslot

{{-- Body --}}
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}
@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
{{ request()->activeBusiness->name }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
    <td>
    <p>@lang(
        "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
        'into your web browser:',
        [
            'actionText' => $actionText,
        ]
    ) <span class="break-all"><a href="{{ $actionUrl }}">{{ $displayableActionUrl }}</a></span>
    </td>
</tr>
</table>
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ request()->activeBusiness->name }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent


