@extends('layouts.main')

@section('content')
<div class="container">
    <h2>Menu</h2>
    <ul>
        <li><a href="{{route('reports.index')}}">Report</a></li>
        <li><a href="{{route('database.index')}}">Database Setting</a></li>
    </ul>
</div>
@endsection
