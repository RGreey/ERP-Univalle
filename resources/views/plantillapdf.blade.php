<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Evento</title>
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <style>
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            display: flex;
            justify-content: center; 
            align-items: center;
            margin-bottom: 20px;
        }
        .header div {
            display: flex;
            align-items: center; 
        }
        .header img {
            max-height: 100px;
            margin-right: 20px; 
        }
        .header h2, .header h3 {
            margin: 0; 
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .section-title {
            background-color: #cd1f32;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: center;
        }
        .estado {
            font-weight: bold;
            color: {{ $informacionEvento['evento']->estado === 'Aceptado' ? '#28a745' : ($informacionEvento['evento']->estado === 'Rechazado' ? '#dc3545' : ($informacionEvento['evento']->estado === 'Cancelado' ? '#6c757d' : ($informacionEvento['evento']->estado === 'Cerrado' ? '#007bff' : '#007bff'))) }};
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <img src="{{ public_path('imagenes/logou.png') }}" alt="Logo de la universidad">
            <div >
                <h2>Universidad del Valle</h2>
                <h3>Erp-manager Informe del evento</h3>
            </div>
        </div>
    </div>
    <table>
        <tr>
            <th class="section-title" colspan="2">Información del Evento</th>
        </tr>
        <tr>
            <td><strong>Nombre del Evento:</strong></td>
            <td>{{ $informacionEvento['evento']->nombreEvento }}</td>
        </tr>
        <tr>
            <td><strong>Propósito del Evento:</strong></td>
            <td>{{ $informacionEvento['evento']->propositoEvento }}</td>
        </tr>
        <tr>
            <td><strong>Programas o Dependencias:</strong></td>
            <td>
                <ul>
                    @foreach($informacionEvento['programasDependencias'] as $dependencia)
                        <li>{{ $dependencia }}</li>
                    @endforeach
                </ul>
            </td>
        </tr>

        <tr>
            <td><strong>Fecha:</strong></td>
            <td>{{ $informacionEvento['evento']->fechaRealizacion }}</td>
        </tr>
        <tr>
            <td><strong>Hora de Inicio:</strong></td>
            <td>{{ $informacionEvento['evento']->horaInicio }}</td>
        </tr>
        <tr>
            <td><strong>Hora de Fin:</strong></td>
            <td>{{ $informacionEvento['evento']->horaFin }}</td>
        </tr>
        <tr>
            <td><strong>Lugar:</strong></td>
            <td>{{ $informacionEvento['lugar']}}</td>
        </tr>
        <tr>
            <td><strong>Espacio:</strong></td>
            <td>{{ $informacionEvento['espacio']}}</td>
        </tr>
        <tr>
            <td><strong>Estado:</strong></td>
            <td class="estado">{{ $informacionEvento['evento']->estado }}</td>
        </tr>
        <tr>
            <td><strong>Organizador:</strong></td>
            <td>{{ $informacionEvento['organizador'] ?? 'No disponible' }}</td>
        </tr>
        <tr>
            <td><strong>Correo del Organizador:</strong></td>
            <td>{{ $informacionEvento['correoOrganizador'] ?? 'No disponible' }}</td>
        </tr>
    </table>
    <table>
        <tr>
            <th class="section-title" colspan="2">Inventario del Evento</th>
        </tr>
        @foreach($informacionEvento['inventarioEvento'] as $inventario)
        <tr>
            <td><strong>Nombre:</strong></td>
            <td>{{ $inventario->tipo }}</td>
        </tr>
        <tr>
            <td><strong>Cantidad:</strong></td>
            <td>{{ $inventario->cantidad }}</td>
        </tr>
        @endforeach
    </table>

    <table>
        <tr>
            <th class="section-title" colspan="2">Descripción</th>
        </tr>
        <tr>
            <td><strong>Detalles del Evento:</strong></td>
            <td>
                <ul>
                    @foreach($informacionEvento['detallesEvento']->getAttributes() as $detalle => $value)
                    @if($value && $detalle !== 'evento' && $detalle !== 'id' && $detalle !== 'created_at' && $detalle !== 'updated_at' && $detalle !== 'otros' && !in_array($detalle, ['estudiantes', 'profesores', 'administrativos', 'empresarios', 'comunidad_general', 'egresados', 'invitados_externos']))
                            <li>{{ ucfirst(str_replace('_', ' ', $detalle)) }}: Sí</li>
                        @endif
                    @endforeach
                </ul>
            </td>
        </tr>
        <tr>
            <td><strong>Otros:</strong></td>
            <td>{{ $informacionEvento['detallesEvento']->otros ? $informacionEvento['detallesEvento']->otros : 'No especificado' }}</td>
        </tr>  
        <td><strong>Participantes:</strong></td>
            <td>
                <ul>
                    @foreach(['estudiantes', 'profesores', 'administrativos', 'empresarios', 'comunidad_general', 'egresados', 'invitados_externos'] as $participante)
                        @if($informacionEvento['detallesEvento']->$participante)
                            <li>{{ ucfirst(str_replace('_', ' ', $participante)) }}</li>
                        @endif
                    @endforeach
                </ul>
            </td>          
    </table>
    <!-- Nueva página para el historial de anotaciones -->
<div class="page-break" style="page-break-before: always;"></div>
<h3>Historial de Anotaciones</h3>
<table width="100%" border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Fecha y Hora</th>
            <th>Usuario</th>
            <th>Contenido</th>
            <th>Archivo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($informacionEvento['anotaciones'] as $anotacion)
            <tr>
                <td>{{ $anotacion->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $anotacion->usuario->name }}</td>
                <td>{{ $anotacion->contenido }}</td>
                <td>
                    @if($anotacion->archivo_url)
                        <a href="{{ $anotacion->archivo_url }}">Ver archivo</a>
                    @else
                        No disponible
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No hay anotaciones para este evento.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
