<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrevista Programada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e3a8a;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e2e8f0;
        }
        .info-box {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .highlight {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #6b7280;
            font-size: 14px;
        }
        .important {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 Entrevista Programada</h1>
        <p>Monitoría Universitaria</p>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        
        <p>Te informamos que se ha programado tu entrevista para la monitoría:</p>
        
        <div class="info-box">
            <h3>📚 {{ $monitoria->nombre }}</h3>
            <p><strong>Encargado:</strong> {{ $entrevistador->name }}</p>
        </div>
        
        <div class="highlight">
            <h4>📅 Detalles de la Entrevista:</h4>
            <p><strong>Fecha y Hora:</strong> {{ \Carbon\Carbon::parse($postulado->entrevista_fecha)->format('d/m/Y \a \l\a\s g:i A') }}</p>
            <p><strong>Medio:</strong> 
                @if($postulado->entrevista_medio == 'presencial')
                    🏢 Presencial
                @else
                    💻 Virtual
                @endif
            </p>
            
            @if($postulado->entrevista_medio == 'presencial' && $postulado->entrevista_lugar)
                <p><strong>📍 Lugar:</strong> {{ $postulado->entrevista_lugar }}</p>
            @endif
            
            @if($postulado->entrevista_medio == 'virtual' && $postulado->entrevista_link)
                <p><strong>🔗 Link de la reunión:</strong> <a href="{{ $postulado->entrevista_link }}" target="_blank">{{ $postulado->entrevista_link }}</a></p>
            @endif
        </div>
        
        @if($postulado->concepto_entrevista)
            <div class="info-box">
                <h4>📝 Información Adicional:</h4>
                <p>{{ $postulado->concepto_entrevista }}</p>
            </div>
        @endif
        
        <div class="highlight">
            <h4>⚠️ Importante:</h4>
            <ul>
                <li>Llega 5 minutos antes de la hora programada</li>
                @if($postulado->entrevista_medio == 'virtual')
                    <li>Asegúrate de tener una conexión estable a internet</li>
                    <li>Prueba tu cámara y micrófono antes de la entrevista</li>
                @else
                    <li>Lleva tu documento de identidad</li>
                    <li>Viste de manera apropiada</li>
                @endif
                <li>Es una conversación para conocerte mejor y resolver dudas sobre la monitoría</li>
            </ul>
        </div>
        
        <p>Si tienes alguna pregunta o necesitas reprogramar la entrevista, por favor contacta al encargado de la monitoría.</p>
        
        <p>¡Te deseamos mucho éxito en tu entrevista!</p>
        
        <p>Saludos,<br>
        <strong>ERP Manager</strong><br>
        Universidad del Valle</p>
    </div>
    
    <div class="footer">
        <p>Este es un correo automático del ERP Manager.</p>
        <p>Si tienes dudas, contacta a soporte.caicedonia@correounivalle.edu.co</p>
    </div>
</body>
</html>
