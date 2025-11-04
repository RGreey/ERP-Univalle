<?php

namespace App\Http\Controllers;

use App\Models\StandbyRegistro;
use App\Models\ConvocatoriaSubsidio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminStandbyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    /**
     * Listado con filtros: activo, externos, día+sede, búsqueda por nombre/email/código.
     * Nota: si Sede tiene valor y Día es "Todos", filtra por esa sede en CUALQUIER día.
     */
    public function index(Request $request)
    {
        $convId   = $request->integer('convocatoria_id');
        $activo   = $request->input('activo');           // '1','0', null
        $externos = $request->boolean('externos');       // true/false
        $dia      = trim((string) $request->input('dia')) ?: '';   // 'lun','mar','mie','jue','vie' o ''
        $sede     = trim((string) $request->input('sede')) ?: '';  // 'caicedonia','sevilla','ninguno' o ''
        $q        = trim((string) $request->input('q'));

        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        if (!$convId && $convocatorias->count() > 0) {
            $convId = (int) $convocatorias->first()->id;
        }

        // Precalcular IDs de usuarios cuyo "código" coincide con la búsqueda
        $userIdsByCodigo = [];
        if ($q !== '') {
            $userIdsByCodigo = $this->findUserIdsByCodigo($q, $convId);
        }

        $query = StandbyRegistro::with(['user:id,name,email','convocatoria:id,nombre'])
            ->when($convId, fn($q2) => $q2->where('convocatoria_id', $convId))
            ->when($activo !== null && $activo !== '', fn($q2) => $q2->where('activo', (bool) $activo))
            ->when($externos, fn($q2) => $q2->where('es_externo', true))
            // Caso 1: Día y Sede definidos -> pref_dia = sede
            ->when(
                $dia !== '' && in_array($dia, ['lun','mar','mie','jue','vie'], true) && $sede !== '',
                fn($q2) => $q2->where('pref_'.$dia, $sede)
            )
            // Caso 2: solo Sede definida (Día = "Todos") -> cualquier día = sede
            ->when(
                ($dia === '' || !in_array($dia, ['lun','mar','mie','jue','vie'], true)) && $sede !== '',
                function ($q2) use ($sede) {
                    $q2->where(function ($w) use ($sede) {
                        $w->where('pref_lun', $sede)
                          ->orWhere('pref_mar', $sede)
                          ->orWhere('pref_mie', $sede)
                          ->orWhere('pref_jue', $sede)
                          ->orWhere('pref_vie', $sede);
                    });
                }
            )
            // Búsqueda por nombre, email o código
            ->when($q !== '', function ($q2) use ($q, $userIdsByCodigo) {
                $q2->where(function ($sub) use ($q, $userIdsByCodigo) {
                    $sub->whereHas('user', function ($uq) use ($q) {
                        $uq->where('name', 'like', "%$q%")
                           ->orWhere('email', 'like', "%$q%");
                    });
                    if (!empty($userIdsByCodigo)) {
                        $sub->orWhereIn('user_id', $userIdsByCodigo);
                    }
                });
            });

        $items = $query
            ->orderByDesc('activo')
            ->orderBy('created_at', 'asc')
            ->paginate(25)
            ->withQueryString();

        // Inyectar user->codigo (desde respuestas de encuesta) para mostrarlo en la tabla
        $this->injectCodigosIntoPaginator($items, $convId);

        return view('roles.adminbienestar.standby.index', [
            'items'         => $items,
            'convocatorias' => $convocatorias,
            'convId'        => $convId,
            'filtros'       => compact('activo','externos','dia','sede','q'),
        ]);
    }

    /**
     * Form para crear: permite cargar por correo.
     */
    public function create()
    {
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        return view('roles.adminbienestar.standby.create', [
            'convocatorias' => $convocatorias,
            'sedes' => ['caicedonia','sevilla','ninguno'],
            'dias'  => ['lun'=>'Lunes','mar'=>'Martes','mie'=>'Miércoles','jue'=>'Jueves','vie'=>'Viernes'],
        ]);
    }

    /**
     * Crea (o reutiliza) un usuario por correo y lo agrega a la lista de standby.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','exists:convocatorias_subsidio,id'],
            'email'           => ['required','email','max:190'],
            'name'            => ['nullable','string','max:190'],
            'es_externo'      => ['nullable','boolean'],
            'activo'          => ['nullable','boolean'],
            'pref_lun'        => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mar'        => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mie'        => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_jue'        => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_vie'        => ['required','in:caicedonia,sevilla,ninguno'],
        ]);

        $user = User::where('email', $data['email'])->first();

        DB::transaction(function () use (&$user, $data) {
            if (!$user) {
                $user = new User();
                $user->name  = $data['name'] ?: Str::before($data['email'], '@');
                $user->email = $data['email'];
                $user->rol   = 'Estudiante';
                $user->password = Hash::make(Str::random(24));
                $user->email_verified_at = now(); // quita esta línea si quieres verificación real
                $user->save();
            }

            $exists = StandbyRegistro::where('convocatoria_id', $data['convocatoria_id'])
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                abort(409, 'El usuario ya está en standby para esta convocatoria.');
            }

            StandbyRegistro::create([
                'convocatoria_id' => $data['convocatoria_id'],
                'user_id'         => $user->id,
                'es_externo'      => (bool) ($data['es_externo'] ?? false),
                'activo'          => (bool) ($data['activo'] ?? true),
                'pref_lun'        => $data['pref_lun'],
                'pref_mar'        => $data['pref_mar'],
                'pref_mie'        => $data['pref_mie'],
                'pref_jue'        => $data['pref_jue'],
                'pref_vie'        => $data['pref_vie'],
            ]);
        });

        return redirect()->route('admin.standby.index', ['convocatoria_id' => $data['convocatoria_id']])
            ->with('success', 'Usuario agregado a standby.');
    }

    public function edit(StandbyRegistro $registro)
    {
        $registro->load(['user:id,name,email','convocatoria:id,nombre']);

        $map = $this->getCodigosForUserIds([$registro->user_id], $registro->convocatoria_id);
        if ($registro->user) {
            $registro->user->codigo = $map[$registro->user_id] ?? null;
        }

        return view('roles.adminbienestar.standby.edit', compact('registro'));
    }

    public function update(Request $request, StandbyRegistro $registro)
    {
        $data = $request->validate([
            'es_externo' => ['nullable','boolean'],
            'activo'     => ['nullable','boolean'],
            'pref_lun'   => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mar'   => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mie'   => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_jue'   => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_vie'   => ['required','in:caicedonia,sevilla,ninguno'],
        ]);

        $registro->update([
            'es_externo' => (bool) ($data['es_externo'] ?? false),
            'activo'     => (bool) ($data['activo'] ?? true),
            'pref_lun'   => $data['pref_lun'],
            'pref_mar'   => $data['pref_mar'],
            'pref_mie'   => $data['pref_mie'],
            'pref_jue'   => $data['pref_jue'],
            'pref_vie'   => $data['pref_vie'],
        ]);

        return back()->with('success','Actualizado.');
    }

    public function toggleActivo(StandbyRegistro $registro)
    {
        $registro->activo = ! $registro->activo;
        $registro->save();

        return back()->with('success', $registro->activo ? 'Activado' : 'Desactivado');
    }

    public function destroy(StandbyRegistro $registro)
    {
        $convId = $registro->convocatoria_id;
        $registro->delete();

        return redirect()->route('admin.standby.index', ['convocatoria_id' => $convId])
            ->with('success', 'Eliminado.');
    }

    /**
     * Devuelve user_ids cuyo "código" (respuesta_texto) contiene $needle.
     * Restringe por convocatoria cuando se provee.
     */
    protected function findUserIdsByCodigo(string $needle, ?int $convId = null): array
    {
        $preguntaIds = DB::table('subsidio_preguntas')
            ->where(function ($w) {
                $w->whereRaw('LOWER(titulo) LIKE ?', ['%código%'])
                  ->orWhereRaw('LOWER(titulo) LIKE ?', ['%codigo%']);
            })
            ->pluck('id')
            ->all();

        if (empty($preguntaIds)) return [];

        return DB::table('subsidio_postulaciones as p')
            ->join('subsidio_respuestas as r', 'r.postulacion_id', '=', 'p.id')
            ->whereIn('r.pregunta_id', $preguntaIds)
            ->when($convId, fn($q) => $q->where('p.convocatoria_id', $convId))
            ->where('r.respuesta_texto', 'like', "%$needle%")
            ->distinct()
            ->pluck('p.user_id')
            ->all();
    }

    /**
     * Mapa user_id => código (lee subsidio_respuestas.respuesta_texto para preguntas "código").
     */
    protected function getCodigosForUserIds(array $userIds, ?int $convId = null): array
    {
        if (empty($userIds)) return [];

        $preguntaIds = DB::table('subsidio_preguntas')
            ->where(function ($w) {
                $w->whereRaw('LOWER(titulo) LIKE ?', ['%código%'])
                  ->orWhereRaw('LOWER(titulo) LIKE ?', ['%codigo%']);
            })
            ->pluck('id')
            ->all();

        if (empty($preguntaIds)) return [];

        $rows = DB::table('subsidio_postulaciones as p')
            ->join('subsidio_respuestas as r', 'r.postulacion_id', '=', 'p.id')
            ->whereIn('p.user_id', $userIds)
            ->when($convId, fn($q) => $q->where('p.convocatoria_id', $convId))
            ->whereIn('r.pregunta_id', $preguntaIds)
            ->select('p.user_id', 'r.respuesta_texto as codigo', 'r.created_at')
            ->orderBy('r.created_at', 'desc')
            ->get();

        $codigos = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if (!array_key_exists($uid, $codigos) && !empty($row->codigo)) {
                $codigos[$uid] = $row->codigo;
            }
        }

        return $codigos;
    }

    /**
     * Inyecta user->codigo en la colección paginada.
     */
    protected function injectCodigosIntoPaginator($paginator, ?int $convId = null): void
    {
        $userIds = $paginator->pluck('user.id')->filter()->unique()->values()->all();
        if (empty($userIds)) return;

        $codigosMap = $this->getCodigosForUserIds($userIds, $convId);

        foreach ($paginator as $item) {
            $uid = $item->user?->id;
            if ($uid) {
                $item->user->codigo = $codigosMap[$uid] ?? null;
            }
        }
    }
}