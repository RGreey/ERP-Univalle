<!DOCTYPE html>
<html>
<head>
    <title>Notificación de Postulación a Monitoría</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f3f6fb;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .container {
            background: #fff;
            max-width: 600px;
            margin: 40px auto;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.10);
            padding: 36px 30px 28px 30px;
        }
        .header {
            border-bottom: 3px solid #1976d2;
            margin-bottom: 20px;
            padding-bottom: 12px;
        }
        .header h2 {
            color: #1976d2;
            margin: 0;
            font-size: 1.7rem;
            letter-spacing: 0.5px;
        }
        .saludo {
            font-size: 1.13rem;
            margin-bottom: 10px;
            color: #263238;
        }
        .monitoria {
            font-size: 1.08rem;
            margin-bottom: 16px;
            color: #0288d1;
            background: #e1f5fe;
            border-left: 5px solid #0288d1;
            padding: 7px 16px;
            border-radius: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            color: #1976d2;
            font-weight: bold;
            margin-bottom: 7px;
            font-size: 1.09rem;
        }
        .details {
            background: #e3f2fd;
            border-left: 5px solid #1976d2;
            padding: 12px 18px;
            border-radius: 5px;
            margin-bottom: 8px;
            font-size: 1.01rem;
            color: #263238;
        }
        .instructions {
            background: #fff3e0;
            border-left: 5px solid #ff9800;
            padding: 12px 18px;
            border-radius: 5px;
            margin-bottom: 8px;
            font-size: 1.01rem;
            color: #6d4c41;
        }
        .message {
            background: #f8f9fa;
            border-left: 4px solid #bdbdbd;
            color: #555;
            padding: 9px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 0.99rem;
        }
        .adjunto {
            background: #fce4ec;
            border-left: 5px solid #d81b60;
            padding: 12px 18px;
            border-radius: 5px;
            margin-bottom: 8px;
            font-size: 1.01rem;
            color: #ad1457;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.97rem;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Notificación de Postulación a Monitoría</h2>
    </div>
    <div class="saludo">
        <strong>Estimado/a {{ explode(' ', $user->name)[0] }},</strong>
    </div>
    <div class="monitoria">
        <strong>Monitoría de interés:</strong> {{ $monitoria_nombre }}
    </div>
    <div class="section">
        <div class="message">
            Le informamos que se ha realizado una notificación respecto a su postulación a la monitoría.<br>
            <span style="color:#d32f2f;">Por favor, no responda a este correo.</span>
        </div>
    </div>
    <div class="section">
        <span class="section-title">Detalles relevantes:</span>
        <div class="details">{{ $detalles }}</div>
    </div>
    @if(!empty($instrucciones))
    <div class="section">
        <span class="section-title">Instrucciones de corrección:</span>
        <div class="instructions">{{ $instrucciones }}</div>
    </div>
    @endif
    <div class="section">
        <span class="section-title">Adjunto:</span>
        @if($imageUrl)
            <div class="adjunto">
                Se ha adjuntado una imagen a este correo para su referencia.
            </div>
        @else
            <div class="adjunto">No hay imagen adjunta.</div>
        @endif
    </div>
    <div class="footer">
        Universidad del Valle &mdash; Sistema de Monitorías<br>
        <span style="font-size:0.9em;">&copy; {{ date('Y') }} Todos los derechos reservados.</span>
    </div>
</div>
</body>
</html>
