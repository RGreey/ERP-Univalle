<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluación de Desempeño del Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .encabezado-rect {
            border: 2px solid #444;
            border-radius: 18px;
            padding: 0;
            margin-bottom: 12px;
            display: flex;
            align-items: stretch;
            overflow: hidden;
        }
        .rect-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 180px;
            border-right: 2px solid #bbb;
            background: #f5f5f5;
            padding: 8px 18px 8px 18px;
        }
        .rect-info .titulo-rect {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
            border-bottom: 2px solid #bbb;
            color: #222;
        }
        .rect-titulo-grande {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #bbb;
            font-size: 18px;
            font-weight: bold;
            color: #222;
            border-bottom: 2px solid #888;
            border-left: 2px solid #bbb;
            letter-spacing: 1px;
        }
        .datos { width: 100%; margin-bottom: 10px; }
        .datos td { padding: 2px 6px; }
        .tabla-factores { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tabla-factores th, .tabla-factores td { border: 1px solid #333; padding: 4px; text-align: center; }
        .tabla-factores th { background: #BFBFBF !important; color: #000 !important; }
        .tabla-factores td { background: #fff; color: #222; }
        .firma {
            margin-top: 30px;
            text-align: center;
        }
        .pie {
            font-size: 10px;
            text-align: right;
            margin-top: 20px;
            color: #555;
        }
    </style>
</head>
<body>
@php
    $fecha_inicio_dia = $fecha_inicio ? \Carbon\Carbon::parse($fecha_inicio)->format('d') : '';
    $fecha_inicio_mes = $fecha_inicio ? \Carbon\Carbon::parse($fecha_inicio)->format('m') : '';
    $fecha_inicio_anio = $fecha_inicio ? \Carbon\Carbon::parse($fecha_inicio)->format('Y') : '';
    $fecha_fin_dia = $fecha_fin ? \Carbon\Carbon::parse($fecha_fin)->format('d') : '';
    $fecha_fin_mes = $fecha_fin ? \Carbon\Carbon::parse($fecha_fin)->format('m') : '';
    $fecha_fin_anio = $fecha_fin ? \Carbon\Carbon::parse($fecha_fin)->format('Y') : '';
@endphp
<div style="border:2px solid #444; border-radius:18px; overflow:hidden; margin-bottom:12px;">
    <table width="100%" style="border-collapse:separate;">
        <tr>
            <td rowspan="2" style="width:12%; background:#fff; vertical-align:middle;">
                <img src="{{ public_path('imagenes/logobaw.jpg') }}" alt="Logo Univalle" height="60" style="display:block; margin-left:5px; ">
            </td>
            <td colspan="3" style="vertical-align:middle; background:#fff;">
                <div style="font-size:15px; font-weight:bold; color:#222; background:#646464; display:inline-block; margin-bottom:2px;">
                    RECTORÍA
                </div><br>
                <div style="font-size:13px; color:#222; background:#646464; display:inline-block;">
                    Dirección de Regionalización
                </div>
            </td>
            <td colspan="4" rowspan="2" style="vertical-align:middle;">
                <div style="background:#646464; color:#222; font-size:18px; font-weight:bold; text-align:center; letter-spacing:1px;">
                    EVALUACIÓN DE DESEMPEÑO DEL MONITOR
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="height:10px; background:#fff;"></td>
        </tr>
    </table>
</div>
<table style="width:100%; margin-bottom:10px; border: none; font-size: 13px;">
    <tr>
        <td style="width:50%; border:none; padding: 2px 0;"><b>PERÍODO ACADÉMICO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $periodo_academico }}</span></td>
        <td style="width:50%; border:none; padding: 2px 0;"><b>PROGRAMA ACADÉMICO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $programa_academico }}</span></td>
    </tr>
    <tr>
        <td style="border:none; padding: 2px 0;"><b>CÓDIGO ESTUDIANTIL:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $codigo_estudiantil }}</span></td>
        <td style="border:none; padding: 2px 0;"><b>DEPENDENCIA:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $dependencia }}</span></td>
    </tr>
    <tr>
        <td style="border:none; padding: 2px 0;"><b>APELLIDOS DEL ESTUDIANTE:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $apellidos_estudiante }}</span></td>
        <td style="border:none; padding: 2px 0;"><b>NOMBRES DEL ESTUDIANTE:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $nombres_estudiante }}</span></td>
    </tr>
    <tr>
        <td style="border:none; padding: 2px 0;"><b>MODALIDAD DE LA MONITORÍA:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $modalidad_monitoria }}</span></td>
        <td style="border:none; padding: 2px 0;"></td>
    </tr>
</table>
<table style="width:100%; margin-bottom:10px; border: none; font-size: 13px;">
    <tr>
        <td style="width:50%; border:none; padding: 2px 0;"><b>FECHA INICIO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $fecha_inicio_dia }}/{{ $fecha_inicio_mes }}/{{ $fecha_inicio_anio }}</span></td>
        <td style="width:50%; border:none; padding: 2px 0;"><b>FECHA FIN:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $fecha_fin_dia }}/{{ $fecha_fin_mes }}/{{ $fecha_fin_anio }}</span></td>
    </tr>
</table>
<table style="width:100%; margin-bottom:10px; border: none; font-size: 13px;">
    <tr>
        <td style="width:50%; border:none; padding: 2px 0;"><b>EVALUADOR:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $evaluador_nombres }} {{ $evaluador_apellidos }}</span></td>
        <td style="width:50%; border:none; padding: 2px 0;"><b>IDENTIFICACIÓN:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $evaluador_identificacion }}</span></td>
    </tr>
    <tr>
        <td style="border:none; padding: 2px 0;"><b>CARGO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $evaluador_cargo }}</span></td>
        <td style="border:none; padding: 2px 0;"><b>DEPENDENCIA:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $evaluador_dependencia }}</span></td>
    </tr>
</table>
<table class="tabla-factores">
    <thead>
        <tr>
            <th>No.</th>
            <th>Factor</th>
            <th>Calificación (1-5)</th>
        </tr>
    </thead>
    <tbody>
        @php
        $factores = [
            'calidad_trabajo' => 'Calidad del trabajo',
            'sigue_instrucciones' => 'Sigue instrucciones y procedimientos establecidos',
            'responsable_actividad' => 'Es responsable con la actividad asignada',
            'iniciativa' => 'Tiene iniciativa',
            'cumplimiento_horario' => 'Cumplimiento de horario',
            'relaciones_interpersonales' => 'Relaciones interpersonales',
            'cooperacion' => 'Cooperación',
            'atencion_usuario' => 'Atención al usuario',
            'asume_compromisos' => 'Asume los compromisos con la dependencia',
            'maneja_informacion' => 'Maneja información de forma reservada, ética, exclusiva para los fines de la universidad'
        ];
        $i = 1;
        @endphp
        @foreach($factores as $campo => $nombre)
        <tr>
            <td>{{ $i++ }}</td>
            <td style="text-align:left">{{ $nombre }}</td>
            <td>{{ number_format($desempeno->$campo ?? 0, 1) }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2" style="text-align:right"><b>Puntaje Promedio</b></td>
            <td><b>{{ number_format($puntaje_promedio ?? 0, 2) }}</b></td>
        </tr>
    </tbody>
</table>
<div style="margin-bottom:10px; font-size:13px;"><b>Sugerencias y comentarios del evaluador:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $sugerencias }}</span></div>
<div style="margin-bottom:10px; font-size:13px;"><b>Fecha de Evaluación:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $fecha_evaluacion }}</span></div>
<div class="firma" style="min-height:110px;">
    <div style="width:300px;margin:auto;position:relative;height:80px;display:inline-block;">
        @if(!empty($firma_evaluador))
            <img src="{{ $firma_evaluador }}" alt="Firma Evaluador" style="width:70%;max-width:210px;max-height:80px;object-fit:contain;position:absolute;left:50%;bottom:0;transform:translateX(-50%);">
        @endif
        <div style="width:100%;border-bottom:1.5px solid #222;height:0;position:absolute;left:0;bottom:0;"></div>
        <div style="font-size:12px; margin-top:85px;">Firma del Evaluador</div>
    </div>
    <div style="width:300px;margin:auto;position:relative;height:80px;display:inline-block;">
        @if(!empty($firma_evaluado))
            <img src="{{ $firma_evaluado }}" alt="Firma Monitor" style="width:70%;max-width:210px;max-height:80px;object-fit:contain;position:absolute;left:50%;bottom:0;transform:translateX(-50%);">
        @endif
        <div style="width:100%;border-bottom:1.5px solid #222;height:0;position:absolute;left:0;bottom:0;"></div>
        <div style="font-size:12px; margin-top:85px;">Firma del Monitor</div>
    </div>
</div>
<div class="pie">
    F-01-IP-10-04-04-DR V-01-2013 &nbsp;&nbsp;&nbsp; Elaborado por: Dirección de Regionalización
</div>
</body>
</html>
