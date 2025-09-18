<h2 style="color:#0d6efd;">Mantenimiento realizado: {{ $novedad->titulo }}</h2>
<p>Hola {{ $novedad->usuario->name }},</p>
<p>Te informamos que la solicitud de mantenimiento que realizaste ha sido marcada como <b>mantenimiento realizado</b> por el equipo de servicios generales.</p>

<p><b>Descripción de la novedad:</b><br>
{{ $novedad->descripcion }}</p>

@if($novedad->evidencias->count())
    <p><b>Evidencias del trabajo realizado:</b></p>
    <ul>
        @foreach($novedad->evidencias as $evidencia)
            <li>
                <a href="{{ asset('storage/' . $evidencia->archivo_url) }}" target="_blank">Ver evidencia</a>
                @if($evidencia->descripcion)
                    - {{ $evidencia->descripcion }}
                @endif
            </li>
        @endforeach
    </ul>
@endif

<p>Por favor, corrobora que el mantenimiento fue realizado correctamente. Si estás conforme, puedes cerrar la novedad desde el sistema.</p>

<p style="color:#888;">Este es un mensaje automático, por favor no respondas a este correo.</p> 