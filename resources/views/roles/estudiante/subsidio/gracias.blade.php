@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title', 'Postulación enviada')

@section('content')
<div class="container">
    <div class="alert alert-success">
        ¡Tu postulación fue enviada!
    </div>
    <a href="{{ route('subsidio.convocatorias.index') }}" class="btn btn-outline-primary">Volver a convocatorias</a>
</div>
@endsection

@push('scripts')
@if (session('success'))
<script>
Swal.fire({
    title: '¡Gracias!',
    text: @json(session('success')),
    icon: 'success',
    confirmButtonColor: '#cd1f32'
});
</script>
@endif
@endpush