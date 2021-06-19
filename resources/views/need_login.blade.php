<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Need Login</title>
</head>
<body>
	Login dari {{config('identity_provider.app_name', 'Sistem Pusat')}} dibutuhkan. <a href="{{route('login')}}">Klik disini</a> untuk login
</body>
</html>