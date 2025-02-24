<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ get_option('site_title', 'Sales Return') }}</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <link rel="stylesheet" href="{{ public_path('assets/css/style.css') }}">
        <link rel="stylesheet" href="{{ public_path('backend/assets/css/invoice.css') }}">
    </head>
    <body>
        @include('backend.user.sales_return.template.loader', ['type' => 'pdf'])
    </body>
</html>	


