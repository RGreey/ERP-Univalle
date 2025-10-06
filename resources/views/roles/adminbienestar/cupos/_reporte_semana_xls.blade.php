{{-- HTML compatible con Excel (.xls) --}}
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php
    $dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];
?>
<style>
    table { border-collapse: collapse; }
    th, td { border:1px solid #999; padding:4px 6px; vertical-align: top; }
    .sede-title { background:#d9edf7; font-weight:bold; }
    .header { background:#2c3e50; color:#fff; }
</style>

@foreach($dataPorSede as $sede => $map)
    <table>
        <tr><th colspan="5" class="sede-title">Sede: {{ ucfirst($sede) }} — Semana {{ $lunes->format('Y-m-d') }} a {{ $lunes->copy()->addDays(6)->format('Y-m-d') }} — Convocatoria: {{ $convocatoria->nombre }}</th></tr>
        <tr class="header">
            @foreach($dias as $k => $name)
                <th>{{ $name }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($dias as $k => $name)
                <td>
                    @if(!empty($map[$k]))
                        @foreach($map[$k] as $nom)
                            {{ $nom }}<br/>
                        @endforeach
                    @else
                        Sin asignaciones
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
    <br/>
@endforeach