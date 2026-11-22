<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Material Master')</title>
    @stack('styles')

<!-- mm05-modal-layout-fix -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/mm05-redesign.css') }}">

<style>
*, *::before, *::after {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Inter, -apple-system, BlinkMacSystemFont,
                 "Segoe UI", Arial, sans-serif;
    font-size: 14px;
    color: #172b4d;
}

.mm-wizard {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
}

.mm-wizard input,
.mm-wizard select,
.mm-wizard textarea {
    box-sizing: border-box;
    max-width: 100%;
    min-width: 0;
    font-family: inherit;
}

.mm-wizard .mm-field {
    min-width: 0;
}

.mm-wizard .mm-field input,
.mm-wizard .mm-field select,
.mm-wizard .mm-field textarea {
    width: 100%;
}

.mm-wizard h1,
.mm-wizard h2,
.mm-wizard h3,
.mm-wizard p,
.mm-wizard label {
    font-family: inherit;
}

@media (max-width: 760px) {
    .mm-wizard {
        padding: 12px;
    }
}
</style>

</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
