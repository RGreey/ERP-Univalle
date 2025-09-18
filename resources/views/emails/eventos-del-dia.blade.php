<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eventos del Día - Univalle Caicedonia</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f7fa; color: #2c3e50; padding: 20px; margin: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border-collapse: collapse; border: 1px solid #ddd;">
        <tr>
            <td style="background-color: #cd1f32; padding: 20px; text-align: center;">
                <h1 style="color: #ffffff; margin: 10px 0 0;">ERPMANAGER - Eventos del Día</h1>
                <p style="color: #ffffff; margin: 5px 0 0;">Universidad del Valle - Sede Caicedonia</p>
            </td>
        </tr>

        <tr>
            <td style="padding: 20px;">
                <p style="background-color: #FFD100; color: #2c3e50; display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                    Hoy: {{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}
                </p>

                @if(count($nombresEventos) > 0)
                    @foreach ($nombresEventos as $evento)
                        <div style="margin-top: 20px; border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                            <div style="background-color: #cd1f32; color: #ffffff; padding: 15px;">
                                <strong>{{ $evento['evento'] }}</strong><br>
                                <small>🕒 {{ \Carbon\Carbon::parse($evento['horaInicio'])->format('H:i') }}</small>
                            </div>
                            <div style="padding: 15px; color: #333;">
                                {{-- Lugar y espacio --}}
                                <p><strong>📍 Lugar:</strong> 
                                    {{ $evento['nombreEspacio'] ?? 'No especificado' }}
                                </p>

                                {{-- Dependencia organizadora --}}
                                @if(!empty($evento['nombresDependencias']))
                                    <p><strong>👤 Organizador{{ count($evento['nombresDependencias']) > 1 ? 'es' : '' }}:</strong>
                                        {{ implode(', ', $evento['nombresDependencias']) }}
                                    </p>
                                @endif
                                {{-- Propósito o descripción --}}
                                @if(!empty($evento['propositoEvento']))
                                    <p style="margin-top: 10px;">
                                        <strong> 🎯 Proposito:
                                        <em>{{ $evento['propositoEvento'] }}</em>
                                        </strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 30px; border: 2px dashed #ccc; margin-top: 20px;">
                        <p style="font-size: 20px; color: #cd1f32; font-weight: bold;">📅 No hay eventos programados para hoy</p>
                        <p style="color: #666;">Disfruta tu día en la Universidad del Valle - Sede Caicedonia</p>
                    </div>
                @endif
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