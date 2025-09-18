<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajustes requeridos</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; }
        .box { border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .muted { color: #6b7280; font-size: 12px; }
    </style>
 </head>
 <body>
    <p>Hola,</p>
    <p>La monitoría <strong>{{ $monitoria->nombre }}</strong> fue marcada con estado <strong>Requiere ajustes</strong>.</p>
    @if(!empty($comentarios))
        <div class="box">
            <div class="title">Comentarios del revisor</div>
            <p style="white-space: pre-line;">{{ $comentarios }}</p>
        </div>
    @endif
    @if($encargado)
        <p class="muted">Encargado: {{ $encargado->name }} ({{ $encargado->email }})</p>
    @endif
    <p>Por favor realiza los ajustes y vuelve a enviar la solicitud.</p>
    <p>Saludos,<br>ERPmanager</p>
 </body>
 </html>



