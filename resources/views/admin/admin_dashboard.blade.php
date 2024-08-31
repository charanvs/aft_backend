<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dashboard</title>
</head>
<body>
    @if ($errors->any())
    @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
@endif
@if (Session::has('error'))
    <li>{{ Session::get('error') }}</li>    
@endif
@if (Session::has('success'))
<li>{{ Session::get('success') }}</li>    
@endif
<a href="{{route('admin.logout')}}">Logout</a>
</body>
</html>