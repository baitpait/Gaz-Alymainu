{{-- PWA head tags (manifest + iOS home screen) --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#1B6CA8">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'غاز اليمني') }}">
<link rel="apple-touch-icon" href="{{ asset('pwa/apple-touch-icon.png') }}">
