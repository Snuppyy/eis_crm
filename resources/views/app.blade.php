<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @if (!file_exists(public_path('/hot')))
            <link rel="stylesheet" href="{{ mix('js/vendor.css') }}">
        @endif
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    </head>
    <body>
        <div id="app"></div>

        <script>
        window.config = @json([
            'appName' => config('app.name'),
            'locale' => app()->getLocale()
        ]);
        </script>

        @if (!file_exists(public_path('/hot')))
            <script src="{{ mix('js/manifest.js') }}"></script>
            <script src="{{ mix('js/vendor.js') }}"></script>
        @endif
        <script src="{{ mix('js/app.js') }}"></script>
    </body>
</html>
