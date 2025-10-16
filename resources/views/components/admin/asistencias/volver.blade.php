@props([
    // Texto del botón
    'label' => 'Volver a Asistencias',
    // Clases CSS (Bootstrap por defecto)
    'class' => 'btn btn-outline-secondary btn-sm',
    // Lista de claves de query a conservar desde la request actual (string "a,b,c" o array)
    'keep' => null,
    // Parámetros extra a agregar (array asociativo)
    'merge' => [],
])

@php
    use Illuminate\Support\Facades\Route;

    // Construir parámetros finales (keep + merge)
    $params = [];

    if (is_string($keep) && trim($keep) !== '') {
        $keep = array_filter(array_map('trim', explode(',', $keep)));
    }
    if (is_array($keep)) {
        foreach ($keep as $k) {
            if (request()->has($k)) $params[$k] = request()->query($k);
        }
    }
    if (is_array($merge) && !empty($merge)) {
        $params = array_merge($params, $merge);
    }

    // Ruta home del índice de Asistencias
    $href = Route::has('admin.asistencias.index')
        ? route('admin.asistencias.index', $params)
        : url('/admin/asistencias'); // fallback por si cambia el name
@endphp

<a href="{{ $href }}" class="{{ $class }}">{{ $label }}</a>