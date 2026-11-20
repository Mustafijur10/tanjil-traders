@php
    $s = \App\Models\Setting::cached();
    $favicon = $s['store.favicon'] ? asset('storage/' . $s['store.favicon']) : asset('fav.png');
    $title = $s['seo.meta_title'] ?: $s['store.name'];
@endphp
<!doctype html>
<html lang="{{ $s['language.default'] ?? 'en' }}" data-theme="{{ $s['appearance.default_theme'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $s['seo.meta_description'] ?: $s['store.tagline'] }}">
    <meta property="og:site_name" content="{{ $s['store.name'] }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#0a5548">
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">

    {{-- Apply the visitor's last theme and language before anything paints (no white flash in dark mode). --}}
    <script>
        (function () {
            try {
                var t = JSON.parse(localStorage.getItem('tt_theme'));
                var l = JSON.parse(localStorage.getItem('tt_locale'));
                if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t);
                if (l === 'en' || l === 'bn') document.documentElement.setAttribute('lang', l);
            } catch (e) {}
            document.documentElement.style.colorScheme = document.documentElement.getAttribute('data-theme');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap">

    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
    <noscript>Please turn on JavaScript to use {{ $s['store.name'] }}.</noscript>
</body>
</html>
