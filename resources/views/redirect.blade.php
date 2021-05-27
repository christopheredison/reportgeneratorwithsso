<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
  </head>
  <body>
    <script type="text/javascript" src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
    <script type="text/javascript">
      window.location.replace(window.location.href.replace('#', '?'));
    </script>
  </body>
</html>
