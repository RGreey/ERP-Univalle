<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
        }
        .header h2 {
            margin: 0 0 15px 0;
            font-size: 20px;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-info {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            background: rgba(255,255,255,0.8);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }
        .descripcion-general {
            margin: 10px 0;
            padding: 0;
            page-break-inside: avoid;
        }
        .descripcion-general p {
            margin: 0;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .actividad {
            margin-bottom: 30px;
            margin-top: 0px;
        }
        .actividad h3 {
            color: #1565c0;
            border-bottom: 2px solid #e3f2fd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            padding: 12px 15px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .grid-row-group {
            page-break-inside: avoid;
        }

        .grid-cell {
            width: 33.33%;
            height: 200px;
            border: 1px solid #ddd;
            background: #fafafa;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .grid-cell img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            border-radius: 4px;
        }

        .empty-cell {
            border: 1px dashed #ccc;
            background: #f9f9f9;
            color: #999;
            font-style: italic;
        }

        .descripcion-cell {
            width: 100%;
            border: none;
            background: transparent;
            padding: 5px 0;
            text-align: left;
            vertical-align: top;
        }

        .descripcion-cell p {
            margin: 0;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        /* 🔹 Quitamos la fecha debajo */
        .fecha-foto {
            display: none;
        }
        .grid-item.empty-grid-item {
            border: 2px dashed #ccc;
            background: linear-gradient(145deg, #f9f9f9, #f0f0f0);
            box-shadow: none;
        }

        .page-break {
            page-break-before: always;
        }
        .empty-grid-item {
            border: 1px dashed #ccc;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-style: italic;
        }
    </style>
    <title>Evidencias {{ $paquete->sede }} {{ $paquete->mes }}/{{ $paquete->anio }}</title>
</head>
<body>
    <div class="header">
        <h2>Evidencias de Mantenimiento</h2>
        <div class="header-info">
            <strong>Campus: {{ $paquete->sede }}</strong> | 
            <strong>Periodo: {{ str_pad($paquete->mes,2,'0',STR_PAD_LEFT) }}/{{ $paquete->anio }}</strong>
        </div>
    </div>

    @if($paquete->descripcion_general)
        <table class="grid-table">
            <tr>
                <td colspan="3" class="descripcion-cell">
                    <p>{{ $paquete->descripcion_general }}</p>
                </td>
            </tr>
        </table>
    @endif

    @php
        $filasUsadas = 0;
        $maxFilasPorPagina = 3;
        $primerActividad = true;
    @endphp

    @foreach($porActividad as $actividadIndex => $grupo)
        @php
            $fotos = $grupo['fotos'];
            $fotosCount = count($fotos);
            $filasNecesarias = ceil($fotosCount / 3);
        @endphp
        
        {{-- Si no es la primera actividad y no hay espacio suficiente, nueva página --}}
        @if(!$primerActividad && ($filasUsadas + $filasNecesarias) > $maxFilasPorPagina)
            </table>
            </div>
            <div class="page-break"></div>
            @php $filasUsadas = 0; @endphp
        @endif
        
        {{-- Comenzar nueva sección si es necesario --}}
        @if($filasUsadas == 0)
            <div class="actividad">
                <table class="grid-table">
        @endif
        
        {{-- Título de la actividad --}}
        <tr>
            <td colspan="3" style="border: none; background: transparent; padding: 10px 0;">
                <h3 style="margin: 0; color: #1565c0; font-size: 15px; font-weight: 700;">Actividad: {{ $grupo['actividad']->actividad }}</h3>
            </td>
        </tr>
        
        {{-- Filas de fotos --}}
        @php
            $rows = ceil($fotosCount / 3);
            for ($row = 0; $row < $rows; $row++) {
                echo '<tr class="grid-row-group">';
                for ($col = 0; $col < 3; $col++) {
                    $fotoIndex = $row * 3 + $col;
                    if ($fotoIndex < $fotosCount) {
                        $foto = $fotos[$fotoIndex];
                        echo '<td class="grid-cell">';
                        echo '<img src="' . storage_path('app/public/' . $foto->archivo) . '" alt="Evidencia ' . ($fotoIndex + 1) . '">';
                        echo '</td>';
                    } else {
                        echo '<td class="grid-cell empty-cell">Sin evidencia</td>';
                    }
                }
                echo '</tr>';
            }
            
            $filasUsadas += $filasNecesarias;
            $primerActividad = false;
        @endphp
    @endforeach
    
    {{-- Cerrar tabla final --}}
    </table>
    </div>
</body>
</html>



