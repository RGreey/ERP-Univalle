<!DOCTYPE html>
<html>
<head>
    <title>Nueva Anotación</title>
</head>
<body>
    <h1>Nueva Anotación</h1>
    <p>Anotacion agregada a el evento: <strong>{{ $nombreEvento }}</strong></p>
    <p>Contenido: {{ $anotacion->contenido }}</p>
    @if($anotacion->archivo)
        <p>Archivo adjunto: <a href="{{ asset('storages/' . $anotacion->archivo) }}" target="_blank">{{ basename($anotacion->archivo) }}</a></p>
    @endif
</body>
</html>