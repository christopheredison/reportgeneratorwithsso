@extends('layouts.main')

@section('content')
<div class="container">
    <div class="alert alert-info">
        <b>Logged in as:</b> {{$user['name']}} ({{$user['email']}})
    </div>
    <h2>Menu</h2>
    <ul>
        <li><a href="{{route('reports.index')}}">Report</a></li>
        <li><a href="{{route('database.index')}}">Database Setting</a></li>
        <li><a href="{{config('identity_provider.logout_url')}}">Logout</a></li>
    </ul>
</div>
@endsection
