@extends('layouts.app')

@section('title','Mis postulaciones')

@section('content')
<div class="container">
    <h3 class="mb-3">Mis postulaciones</h3>

    @if($postulaciones->isEmpty())
        <div class="alert alert-info">Aún no tienes postulaciones.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Convocatoria</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th>Enviada</th>
                        <th style="width:180px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($postulaciones as $p)
                        <tr>
                            <td>{{ $p->convocatoria->nombre }}</td>
                            <td>{{ $p->sede }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($p->estado) }}</span></td>
                            <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('subsidio.postulaciones.show', $p->id) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                @if($p->documento_pdf)
                                    <a href="{{ route('subsidio.postulaciones.pdf', $p->id) }}" class="btn btn-sm btn-outline-dark">PDF</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $postulaciones->links() }}
    @endif
</div>
@endsection