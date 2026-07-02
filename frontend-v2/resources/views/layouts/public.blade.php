<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('handayani-public.short_name', 'Handayani') . ' — Pendidikan Islam Terpadu')</title>
    <meta name="description" content="@yield('description', config('handayani-public.name', 'Yayasan Lembaga Pendidikan Anak Handayani') . '. KB/PAUD, TK, dan MI Islam terpadu. Portal pembayaran SPP online yang aman.')">

    {{-- Google Fonts: Manrope (display) + Inter (sans) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/public.css', 'resources/js/public.js'])

    @stack('head')
</head>
<body class="min-h-screen bg-background text-foreground font-sans antialiased">
    @yield('content')

    @stack('scripts')
</body>
</html>
