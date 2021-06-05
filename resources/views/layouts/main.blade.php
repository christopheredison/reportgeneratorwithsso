<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ (($title ?? null) ? "$title - " : '') . config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('/css/app.css') }}">
    @yield('style')
  </head>
  <body>
    <div class="container py-5">
      @if($title ?? null)
      <h2 class="pb-2">{{ $title }}</h2>
      @endif
      @yield('content')
    </div>
    <script type="text/javascript" src="{{ asset('/js/app.js') }}"></script>
    @yield('script')
  </body>
</html>
