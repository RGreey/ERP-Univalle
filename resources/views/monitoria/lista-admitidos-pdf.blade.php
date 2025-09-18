<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Admitidos - Monitorías</title>
    <style>
        @page {
            margin: 20px;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
            position: relative;
        }
        
        .header {
            margin-bottom: 30px;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        
        .logo {
            width: 80px;
            height: auto;
            flex-shrink: 0;
        }
        
        .header-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .section-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #666666;
            text-align: left;
        }
        
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 20px 0;
            text-transform: uppercase;
            text-align: left;
        }
        
        .description {
            font-size: 12px;
            text-align: justify;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .subtitle {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 15px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            font-size: 12px;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: auto;
            opacity: 0.2;
            z-index: -1;
            pointer-events: none;
        }
        
        .footer-image {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: auto;
            z-index: -1;
            pointer-events: none;
        }
        
        .header-fixed {
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            z-index: 1;
            padding: 10px 0;
        }
        
        .content {
            margin-top: 10px;
            margin-bottom: 100px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Asegurar que el contenido no se superponga con elementos fijos */
        .table-container {
            margin-bottom: 50px;
        }

        /* Header de primera página (no fijo, evita superposición) */
        .first-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }
        .first-logo {
            width: 80px;
            height: auto;
            flex-shrink: 0;
        }
        .first-header-text {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
    </style>
</head>
<body>
    <!-- Marca de agua de acreditación -->
    <img src="{{ public_path('imagenes/acreditacion.png') }}" class="watermark" alt="Acreditación">
    
    <!-- Footer con imagen -->
    <img src="{{ public_path('imagenes/footerpdf.png') }}" class="footer-image" alt="Footer">
    
    <!-- Header solo en la primera página (flujo normal) -->
    <div class="first-header">
        <img src="{{ public_path('imagenes/logou.png') }}" class="first-logo" alt="Universidad del Valle">
        <div class="first-header-text">
            <div class="section-name">Seccional Caicedonia</div>
            <div class="section-name">Vicerrectoría de Regionalización</div>
        </div>
    </div>
    
    
    <!-- Contenido principal -->
    <div class="content">
        <!-- Título principal -->
        <div class="title">
            Convocatoria Aspirantes a Monitorías de Docencia y Administrativas
        </div>
        
        <!-- Descripción -->
        <div class="description">
                     Con base en la Resolución No.040, de Julio 15 de 2002, del Consejo Superior de la Universidad del Valle, 
             se realiza convocatoria a monitorías administrativas y académicas, para el periodo académico {{ $convocatoria->periodo_academico_nombre ?? '2025-I' }}.
        </div>
        
        <div class="subtitle">Se publica la lista de admitidos.</div>
        
        <!-- Tabla de admitidos -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">DEPENDENCIA</th>
                        <th style="width: 15%;">HORAS A<br>LA<br>SEMANA</th>
                        <th style="width: 10%;">VACANTE</th>
                        <th style="width: 20%;">CC MONITOR</th>
                        <th style="width: 20%;">FECHA DE<br>INICIO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admitidos as $admitido)
                        <tr>
                            <td>{{ $admitido['dependencia'] }}</td>
                            <td style="text-align: center;">{{ $admitido['horas_semana'] }}</td>
                            <td style="text-align: center;">{{ $admitido['vacante'] }}</td>
                            <td style="text-align: center;">{{ $admitido['cc_monitor'] }}</td>
                            <td style="text-align: center;">{{ $admitido['fecha_inicio'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center;">No hay monitores admitidos en la convocatoria actual.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
