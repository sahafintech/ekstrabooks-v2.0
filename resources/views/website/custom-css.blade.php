@php 
$header_color = isset($header_footer_settings->top_header_color) ? $header_footer_settings->top_header_color : '#5034fc';
$footer_color = isset($header_footer_settings->footer_color) ? $header_footer_settings->footer_color : '#061E5C';
$custom_css = isset($header_footer_settings->custom_css) ? $header_footer_settings->custom_css : '';
@endphp

<style type="text/css">
    .top-navbar {
        background-color: {{ $header_color }};
    }
    .footer {
        background: {{ $footer_color }};
    }

    {{ $custom_css }}
</style>