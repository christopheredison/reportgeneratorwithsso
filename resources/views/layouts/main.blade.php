<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report</title>
    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  </head>
  <body>
    <div class="container py-5">
      @yield('content')
    </div>
    <script type="text/javascript" src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/bootstrap.min.js') }}"></script>
  </body>
</html>
