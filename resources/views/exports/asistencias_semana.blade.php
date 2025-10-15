@php
    // 1=Lunes ... 5=Viernes
    $dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];
@endphp

<h3>Resumen semanal de asistencias</h3>
<p>Semana (Lun-Vie): {{ $lunes->toDateString() }} a {{ $lunes->copy()->addDays(4)->toDateString() }}</p>

@forelse($porSede as $sede => $map)
    @php
        $maxFilas = 0;
        for ($d = 1; $d <= 5; $d++) {
            $maxFilas = max($maxFilas, isset($map[$d]) ? count($map[$d]) : 0);
        }
    @endphp

    <h4>Sede: {{ ucfirst($sede) }}</h4>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                @for($d=1;$d<=5;$d++)
                    <th>{{ $dias[$d] }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @for($i=0;$i<$maxFilas;$i++)
                <tr>
                    @for($d=1;$d<=5;$d++)
                        <td>{{ $map[$d][$i] ?? '' }}</td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>

    <br>
@empty
    <p>No hay registros en días hábiles para esta semana.</p>
@endforelse

@if(!empty($alumnos))
    <h3>Ficha de estudiantes de la semana ({{ count($alumnos) }})</h3>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Código</th>
                <th>Programa</th>
                <th>Teléfono</th> {{-- antes decía "Número" --}}
                <th>Email</th>
                <th>Sedes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $al)
                <tr>
                    <td>{{ $al['name'] }}</td>
                    <td>{{ $al['codigo'] ?? '' }}</td>
                    <td>{{ $al['programa'] ?? '' }}</td>
                    <td>{{ $al['numero'] ?? '' }}</td> {{-- aquí llega el teléfono --}}
                    <td>{{ $al['email'] ?? '' }}</td>
                    <td>{{ $al['sedes'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif