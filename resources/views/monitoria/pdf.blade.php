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
        }
        .header-img {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 120px;
            display: block;
        }
        .header-text {
            position: absolute;
            top: 50px;
            left: 160px;
            font-size: 15px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            display: block;
        }
        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
        }
        .footer-img {
            width: 100%; 
            height: auto; 
        }
        @page:first {
            .header-img,
            .header-text {
                display: block;
            }
        }
        @page:last {
            .footer-img {
                display: block;
            }
        }
        h1 {
            text-align: center;
            margin-top: 200px;
            font-size: 20px;
        }
        
        .info-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* Espacio entre el texto y la tabla */
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #9c9c9c;
            color: #ffffff;
            font-size: 14px;
        }
        td {
            font-size: 12px;
        }
        .modalidad {
            font-size: 11px;
            white-space: normal;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .cronograma {
            page-break-before: always; 
            margin-top: 30px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .cronograma p {
            margin: 5px 0;
        }
        .requisitos {
            page-break-before: always;
            margin-top: 30px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .requisitos h2 {
            margin-top: 0;
        }
        .requisitos p {
            margin: 5px 0;
            font-size: 12px;
        }
        .modalidades {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .modalidades p {
            margin: 5px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('imagenes/logou.png') }}" alt="Imagen de Cabecera" class="header-img">
    <div class="header-text">
        Sede Caicedonia<br>
        Dirección de<br>
        Regionalización
    </div>
    
    <h1>CONVOCATORIA ASPIRANTES A MONITORÍAS DE DOCENCIA Y ADMINISTRATIVAS.</h1>
    <p>Con base a la Resolución No.040, de Julio 15 de 2002, del Consejo Superior de la Universidad del Valle,
    se realiza convocatoria a monitorias administrativas y de docencia, para el periodo académico {{ $periodoAcademicoNombre  }}.</p>
    
    <div class="modalidades">
        <h2>MODALIDADES:</h2>
        <p><strong>Monitorías Administrativas:</strong> Se otorgan a estudiantes regulares para realizar actividades técnicas o administrativas en las diferentes dependencias de la Institución, bajo la supervisión de un funcionario (Docente o Administrativo). También pueden apoyar labores ocasionales cuando exista incremento estacional de la carga laboral.</p>
        <p><strong>Monitorías de Docencia:</strong> Apoyo y colaboración en actividades del área docente en una asignatura en particular (material bibliográfico y audiovisual, asesoría, orientación en talleres y tareas), siempre bajo la orientación del profesor responsable. No incluye brindar asesorías sobre temas que sean de su responsabilidad.</p>
        <p><strong>Monitorías de Investigación:</strong> Participación en proyectos de investigación (recolección y análisis de datos, búsqueda bibliográfica, elaboración de informes y tareas afines), bajo la orientación de un docente o investigador responsable.</p>
    </div>


    <table>
        <thead>
            <tr>
                <th>NOMBRE</th>
                <th>VACANTE</th>
                <th>INTENSIDAD</th>
                <th>HORARIO</th>
                <th>REQUISITOS</th>
                <th>MODALIDAD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monitorias as $monitoria)
                <tr>
                    <td>{{ $monitoria->nombre }}</td>
                    <td>{{ $monitoria->vacante }}</td>
                    <td>{{ $monitoria->intensidad }} Horas/Semana</td>
                    <td class="uppercase">{{ $monitoria->horario }}</td>
                    <td>{{ $monitoria->requisitos }}</td>
                    <td class="modalidad">
                        @php $mod = strtolower($monitoria->modalidad); @endphp
                        @if($mod === 'investigacion')
                            Investigación
                        @elseif($mod === 'administrativo')
                            Administrativa
                        @elseif($mod === 'docencia')
                            Docencia
                        @else
                            {{ ucfirst($monitoria->modalidad) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @php
        use Carbon\Carbon;

        function formatDateInSpanish($date) {
            $carbonDate = Carbon::parse($date);
            return $carbonDate->locale('es')->translatedFormat('d \d\e F \d\e Y');
        }

        $fechaApertura = formatDateInSpanish($convocatoriaActiva->fechaApertura);
        $fechaCierre = formatDateInSpanish($convocatoriaActiva->fechaCierre);
        $fechaEntrevistas = formatDateInSpanish($convocatoriaActiva->fechaEntrevistas);
    @endphp

    <!-- Sección de Cronograma -->
    <div class="cronograma">
        <h2>CRONOGRAMA:</h2>
        <p><strong>Modalidades de la monitoría:</strong> Docencia, Administrativa e Investigación</p>
        <p><strong>Perfil del Monitor:</strong> Estudiantes a partir de III Semestre con el conocimiento y la competencia en el área a postularse.</p>
        <p><strong>Periodo de Duración:</strong> Periodo académico {{ $periodoAcademicoNombre }}</p>
        <p><strong>Horario de ejecución:</strong> Diurno y Nocturno</p>
        <p><strong>Plazo para presentar solicitud:</strong> Del {{$fechaApertura}} hasta el {{ $fechaCierre}}.</p>
        <p><strong>Entrevistas:</strong>  {{$fechaEntrevistas}}</p>
        <p><strong>Fecha de inicio:</strong> Previa autorización en Cali</p>
    </div>

    <!-- Sección de Requisitos -->
    <div class="requisitos">
        <h2>REQUISITOS:</h2>
        <p>EN UN SOLO DOCUMENTO ESCANEADO EN PDF</p>
        <p>» Ingresar a la página de la Universidad, descargar el formato Hoja de Vida D-10, diligenciarlo y hacer firmar del respectivo Coordinador del programa de estudios.</p>
        <p>» Diligenciar el formato SOLICITUD DE APOYO SERVICIOS DE BIENESTAR</p>
        <p>» Copia del recibo de pago de matrícula financiera del semestre actual</p>
        <p>» Copia del recibo de pago de servicios públicos de la dirección de residencia actual</p>
        <p>» Carta de solicitud de apoyo económico requerido, soportando porque lo requiere (monitoria)</p>
        <p>» Fotocopia de la cédula de ciudadanía del solicitante y de los padres</p>
        <p>» Copia del tabulado acumulado de matrícula académica</p>
        <p>» Estar matriculado al menos en el 60% de las asignaturas previstas por el Programa Académico, para el respectivo semestre.</p>
        <p>» Haber cursado y aprobado el segundo semestre del Programa Académico en que se encuentre matriculado y haber cubierto al menos el 60% de las asignaturas previstas para los semestres cursados.</p>
        <p>» Acreditar un promedio mínimo de 3.8 (tres, punto, ocho).</p>
        <p>» No haber sido sancionado disciplinariamente y no estar en bajo rendimiento académico.</p>
        
        <p>» Disponibilidad diurna o nocturna, de acuerdo a las necesidades de la dependencia.</p>
        <p>» Demostrar competencia y aptitudes en el área en la cual va a realizar su actividad.</p>
        <p></p>
        <p><strong>Para postularte debes ingresar al ERPMANAGER, dirígete a <a href="https://erpmanager.cloud/">erpmanager.cloud</a>, inicia sesión y busca el módulo de monitorías. Una vez allí, selecciona la(s) monitoría(s) de tu interés y procede a cargar el documento correspondiente.</strong></p>

        <p></p>
        <p><strong>EN LA SELECCIÓN SE TENDRÁ EN CUENTA:</strong> (Según Resolución No.008, de febrero 13 de 2004, del Consejo Superior de la Universidad del Valle).</p>
        <p>» Difícil situación económica (estrato socioeconómico, no percibir ingresos)</p>
        <p>» Buen promedio académico (no ser inferior a 3.8)</p>
        <p>» Disponibilidad de tiempo certificada por el director del Programa respectivo</p>
        <p>» Demostrar competencia y aptitudes en el área en la cual va a realizar su actividad (entrevista)</p>
        <p><strong>Importante:</strong></p>
        <p>» Un estudiante no podrá ser beneficiario de más de una monitoria en forma simultánea.</p>
        <p>» Los estudiantes que accedan a una monitoria recibirán un reconocimiento económico por la realización de las actividades encomendadas. La condición de monitor no generará ningún vínculo laboral entre el estudiante beneficiario y la Universidad.</p>
    </div>


    <div class="footer">
        <img src="{{ public_path('imagenes/footer.png') }}" alt="Imagen de Footer" class="footer-img">
    </div>
</body>
</html>
