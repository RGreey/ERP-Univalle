@extends('layouts.app')

@section('title', 'Nueva Convocatoria Subsidio')

@section('content')
<div class="container">
    <h2 class="mb-3">Nueva Convocatoria</h2>
    <form action="{{ route('admin.convocatorias-subsidio.store') }}" method="POST">
        @csrf
        @include('roles.adminbienestar.convocatorias_subsidio._form')
    </form>
</div>
@endsection