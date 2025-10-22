@extends('layouts.app')
@section('title','Resultado de oferta')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          @if(!empty($ok) && $ok)
            <div class="alert alert-success mb-3">
              {{ $msg ?? 'Operación exitosa.' }}
            </div>
            @php
              $c = $cupo ?? ($oferta->cupo ?? null);
              $fecha = optional($c?->fecha)->format('Y-m-d');
              $sede  = ucfirst($c?->sede ?? '');
            @endphp
            <p>
              Te hemos asignado el cupo de {{ $fecha ? "hoy ($fecha)" : 'hoy' }} en la sede {{ $sede }}.
            </p>
            <p class="text-muted mb-0">Presenta tu documento en el restaurante para el registro.</p>
          @else
            <div class="alert alert-danger mb-3">
              {{ $msg ?? 'No fue posible completar la operación.' }}
            </div>
            <p class="mb-0">
              Es posible que la oferta haya expirado o que el cupo haya sido tomado por otra persona.
            </p>
          @endif
        </div>
      </div>
      <div class="text-center mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('home') }}">Ir al inicio</a>
      </div>
    </div>
  </div>
</div>
@endsection