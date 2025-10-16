@props([
    // 1) Nombre de ruta (opcional). Si no se pasa, intenta previous dentro de /admin/ o home.
    'to' => null,
    // 2) URL directa (opcional). Si está, tiene prioridad sobre 'to'.
    'href' => null,
    // 3) Lista de claves (string "a,b,c" o array) que se copian desde la request como query (?a=..&b=..).
    'keep' => null,
    // 4) Parámetros extra para la ruta/URL (array asociativo). Se mezclan con 'keep'.
    'merge' => [],
    // 5) Texto del botón
    'label' => 'Volver',
    // 6) Clases CSS
    'class' => 'btn btn-outline-secondary btn-sm',
])

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route as RouteFacade;

    // Home por defecto (dashboard principal AdminBienestar)
    $homeName = (string) config('adminbienestar.home_route', 'admin.subsidio.admin.dashboard');

    // Construir params desde keep + merge
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

    // Resolver URL final
    $finalHref = null;

    if (!empty($href)) {
        // URL directa
        $finalHref = (string) $href;
        if (!empty($params)) {
            $finalHref .= (parse_url($finalHref, PHP_URL_QUERY) ? '&' : '?').http_build_query($params);
        }
    } else {
        // Intentar por nombre de ruta
        if (!empty($to) && RouteFacade::has($to)) {
            try {
                $finalHref = route($to, $params);
            } catch (\Throwable $e) {
                $finalHref = null; // si faltan params requeridos, caemos al fallback
            }
        }

        // Fallback: previous dentro de /admin/
        if (!$finalHref) {
            $prev = url()->previous();
            if ($prev && Str::contains($prev, '/admin/')) {
                $finalHref = $prev;
                if (!empty($params)) {
                    $finalHref .= (parse_url($finalHref, PHP_URL_QUERY) ? '&' : '?').http_build_query($params);
                }
            }
        }

        // Fallback final: dashboard
        if (!$finalHref) {
            $finalHref = RouteFacade::has($homeName) ? route($homeName) : url('/admin');
            if (!empty($params)) {
                $finalHref .= (parse_url($finalHref, PHP_URL_QUERY) ? '&' : '?').http_build_query($params);
            }
        }
    }
@endphp

<a href="{{ $finalHref }}" class="{{ $class }}">{{ $label }}</a>