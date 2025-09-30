@extends('layouts.app')

@section('title', 'Editar Convocatoria Subsidio')

@section('content')
<div class="container">
    <h2 class="mb-3">Editar Convocatoria</h2>
    <form action="{{ route('admin.convocatorias-subsidio.update', $convocatoria->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('roles.adminbienestar.convocatorias_subsidio._form', ['convocatoria' => $convocatoria])
    </form>
</div>
@endsection