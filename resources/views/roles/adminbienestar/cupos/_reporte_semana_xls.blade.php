@php
    // $dataPorSede: ['caicedonia' => [1=>[nombres],2=>[],...], 'sevilla' => [...]]
    // $dias: [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes']
@endphp

@foreach($dataPorSede as $sede => $map)
    @php
        // Asegurar llaves 1..5
        $map = array_replace([1=>[],2=>[],3=>[],4=>[],5=>[]], $map ?? []);
        $counts = array_map(fn($arr)=> is_array($arr) ? count($arr) : 0, $map);
        $maxFilas = max($counts); // filas de estudiantes
    @endphp

    <table>
        <tr>
            <th colspan="5" style="font-weight:700;">
                Sede: {{ ucfirst($sede) }} — Semana {{ $lunes->format('Y-m-d') }} a {{ $lunes->copy()->addDays(6)->format('Y-m-d') }} — Convocatoria: {{ $convocatoria->nombre }}
            </th>
        </tr>
        <tr>
            @foreach($dias as $k => $name)
                <th>{{ $name }}</th>
            @endforeach
        </tr>

        @if($maxFilas === 0)
            <tr>
                @foreach($dias as $k => $name)
                    <td>Sin asignaciones</td>
                @endforeach
            </tr>
        @else
            @for($i=0; $i < $maxFilas; $i++)
                <tr>
                    @foreach($dias as $k => $name)
                        <td>
                            {{ $map[$k][$i] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @endfor
        @endif
    </table>

    {{-- Separación entre sedes --}}
    <br/>
@endforeach