<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eventos sin Gestionar - Univalle Caicedonia</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f7fa; color: #2c3e50; padding: 20px; margin: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border-collapse: collapse; border: 1px solid #ddd;">
        <tr>
            <td style="background-color: #cd1f32; padding: 20px; text-align: center;">
                <h1 style="color: #ffffff; margin: 10px 0 0;">⚠️ Eventos sin Gestionar</h1>
                <p style="color: #ffffff; margin: 5px 0 0;">Universidad del Valle - Sede Caicedonia</p>
            </td>
        </tr>

        <tr>
            <td style="padding: 20px;">
                <p style="background-color: #FFD100; color: #2c3e50; display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                    Notificación del {{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}
                </p>

                <p style="margin-top: 20px; font-size: 16px;">
                    Los siguientes eventos están programados para dentro de <strong>2 días</strong> y aún están en estado <strong>"creado"</strong>. Se recomienda gestionarlos lo antes posible:
                </p>

                @foreach($eventos as $evento)
                    <div style="margin-top: 15px; border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                        <div style="background-color: #f9f9f9; padding: 15px;">
                            <strong style="font-size: 16px;">📌 {{ $evento->nombreEvento }}</strong><br>
                            <p style="margin: 5px 0;">
                                <strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($evento->fechaRealizacion)->locale('es')->translatedFormat('d \d\e F, Y') }}
                            </p>
                            <p style="margin: 5px 0;">
                                <strong>🏛️ Espacio:</strong> {{ $evento->espacioFisico->nombre ?? 'No especificado' }}
                            </p>
                            @if($evento->propositoEvento)
                                <p style="margin-top: 10px; color: #555;"><em>{{ $evento->propositoEvento }}</em></p>
                            @endif
                        </div>
                    </div>
                @endforeach

                <p style="margin-top: 30px; font-size: 14px;">
                    Por favor revise estos eventos y tome las acciones necesarias para asegurar su correcta realización.
                </p>
            </td>
        </tr>

        <tr>
            <td style="text-align: center; padding: 20px; font-size: 12px; color: #888;">
                © {{ now()->year }} Universidad del Valle - Sede Caicedonia | <strong style="color: #cd1f32;">ERPMANAGER</strong>
            </td>
        </tr>
    </table>
</body>
</html>