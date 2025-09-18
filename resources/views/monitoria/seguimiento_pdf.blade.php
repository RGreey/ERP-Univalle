<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento Monitoría Sedes</title>
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

        .rect-textos {
            flex: 1;
            display: flex;
            flex-direction: row;
            align-items: stretch;
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
        .rect-info .subtitulo-rect {
            font-size: 11px;
            color: #222;
            border-bottom: 2px solid #bbb;
            margin-bottom: 0;
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
        .same-height {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .same-height-cell {
            display: table-cell;
            vertical-align: top;
            padding: 0 10px;
        }
        .datos { width: 100%; margin-bottom: 10px; }
        .datos td { padding: 2px 6px; }
        .tabla-actividades { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tabla-actividades th, .tabla-actividades td { border: 1px solid #333; padding: 4px; text-align: center; }
        .tabla-actividades th { background: #BFBFBF !important; color: #000 !important; }
        .tabla-actividades td { background: #fff; color: #222; }
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
        .contenedor-principal {
            min-height: 650px;
            display: flex;
            flex-direction: column;
        }
        .contenido-flex {
            flex: 1 0 auto;
        }
        .footer-flex {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<div style="border:2px solid #444; border-radius:18px; overflow:hidden; margin-bottom:12px;">
    <table width="100%" style="border-collapse:separate;">
        <tr>
            <!-- Logo (1/8) -->
            <td rowspan="2" style="width:12%; background:#fff; vertical-align:middle;">
                <img src="{{ public_path('imagenes/logobaw.jpg') }}" alt="Logo Univalle" height="60" style="display:block; margin-left:5px; ">
            </td>
            <!-- Rectoria y Dirección (3/8) -->
            <td colspan="3" style="vertical-align:middle; background:#fff;">
                <div style="font-size:15px; font-weight:bold; color:#222; background:#646464; display:inline-block; margin-bottom:2px;">
                    RECTORÍA
                </div><br>
                <div style="font-size:13px; color:#222; background:#646464; display:inline-block;">
                    Dirección de Regionalización
                </div>
            </td>
            <!-- Título grande (4/8) -->
            <td colspan="4" rowspan="2" style="vertical-align:middle;">
                <div style="background:#646464; color:#222; font-size:18px; font-weight:bold; text-align:center; letter-spacing:1px;">
                    SEGUIMIENTO MONITORÍAS SEDES
                </div>
            </td>
        </tr>
        <tr>
            <!-- Espacio vacío para alinear verticalmente -->
            <td colspan="3" style="height:10px; background:#fff;"></td>
        </tr>
    </table>
    </div>
    <div class="contenedor-principal">
        <div class="contenido-flex">
            <!-- DATOS PRINCIPALES EN FILAS ESTRICTAS Y JUSTIFICADAS -->
            <table style="width:100%; margin-bottom:10px; border: none; font-size: 13px;">
                <tr>
                    <td style="width:50%; border:none; padding: 2px 0;"><b>SEDE:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $sede }}</span></td>
                    <td style="width:50%; border:none; padding: 2px 0;">
                        <b>PERSONA QUE<br>SOLICITÓ LA MONITORÍA:</b> <span style="border-bottom:1px solid #222; padding:0 20px; vertical-align:bottom; display:inline-block; min-width:120px;">{{ $solicitante }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding: 2px 0;"><b>PERIODO DE LA MONITORÍA:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $periodo }}</span></td>
                    <td style="border:none; padding: 2px 0;"><b>PROCESO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $proceso ?? '' }}</span></td>
                </tr>
                <tr>
                    <td style="border:none; padding: 2px 0;"><b>PERIODO A PAGAR:</b> <span style="border-bottom:1px solid #222; display:inline-block; width:120px; min-height:16px;">{{ isset($periodo_pagar) && $periodo_pagar ? $periodo_pagar : '' }}</span></td>
                    <td style="border:none; padding: 2px 0;"><b>SUBPROCESO:</b> <span style="border-bottom:1px solid #222; display:inline-block; width:120px; min-height:16px;">{{ isset($subproceso) && $subproceso ? $subproceso : '' }}</span></td>
                </tr>
                <tr>
                    <td style="border:none; padding: 2px 0;"><b>NOMBRE ESTUDIANTE:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $monitor->nombre }}</span></td>
                    <td style="border:none; padding: 2px 0;"><b>PLAN ACADÉMICO:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $plan_academico ?? '' }}</span></td>
                </tr>
                <tr>
                    <td style="border:none; padding: 2px 0;"></td>
                    <td style="border:none; padding: 2px 0;"><b>CEDULA DE CIUDADANÍA:</b> <span style="border-bottom:1px solid #222; padding:0 20px;">{{ $monitor->cedula }}</span></td>
                </tr>
            </table>
            <table class="tabla-actividades">
                <thead>
                    <tr>
                        <th>FECHA MONITORÍA</th>
                        <th>HORA INGRESO</th>
                        <th>HORA SALIDA</th>
                        <th>TOTAL HORAS</th>
                        <th>ACTIVIDAD REALIZADA</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($actividades as $a)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($a->fecha_monitoria)->format('d/m/Y') }}</td>
                            <td>{{ $a->hora_ingreso }}</td>
                            <td>{{ $a->hora_salida }}</td>
                            <td>{{ $a->total_horas }}</td>
                            <td style="text-align:left">{{ $a->actividad_realizada }}</td>
                        </tr>
                        @php $total += (int) $a->total_horas; @endphp
                    @endforeach
                    <tr>
                        <td colspan="3"><b>TOTAL HORAS</b></td>
                        <td colspan="2"><b>{{ $total }}</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="footer-flex">
            <div class="firma" style="min-height:110px;">
                <div style="width:300px;margin:auto;position:relative;height:80px;">
                    @if(!empty($actividades->first()->firma_digital))
                        <img src="{{ $actividades->first()->firma_digital }}" alt="Firma Digital"
                             style="width:{{ $actividades->first()->firma_size ?? 70 }}%;max-width:210px;max-height:80px;object-fit:contain;position:absolute;left:50%;bottom:{{ $actividades->first()->firma_pos ?? -15 }}px;transform:translateX(-50%);">
                    @endif
                    <div style="width:100%;border-bottom:1.5px solid #222;height:0;position:absolute;left:0;bottom:0;"></div>
                </div>
                Firma Responsable de Seguimiento de Monitoría
            </div>
            <div class="pie">
                F-01-IP-10-04-04-DR V-01-2013 &nbsp;&nbsp;&nbsp; Elaborado por: Dirección de Regionalización
            </div>
        </div>
    </div>
</body>
</html> 
