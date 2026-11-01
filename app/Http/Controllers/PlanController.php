<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SistemaSaas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }

    public function index(Request $request)
    {
        $sistemas = SistemaSaas::orderBy('nombre')->get();

        $planes = Plan::with('sistema', 'escalas')
            ->when($request->filled('sistema'), fn ($q) => $q->where('id_sistema', $request->sistema))
            ->orderBy('id_sistema')
            ->orderBy('precio')
            ->get();

        return view('planes.index', compact('planes', 'sistemas'));
    }

    public function create(Request $request)
    {
        $sistemas = SistemaSaas::orderBy('nombre')->get();
        $plan = new Plan([
            'id_sistema' => $request->query('sistema'),
            'tipo' => 'recurrente',
            'duracion_meses' => 1,
            'moneda' => 'ARS',
            'unidad_minima' => 1,
            'activo' => true,
        ]);

        return view('planes.create', compact('plan', 'sistemas'));
    }

    public function store(Request $request)
    {
        [$data, $escalas] = $this->validar($request);

        DB::transaction(function () use ($data, $escalas, &$plan) {
            $plan = Plan::create($data);
            $this->guardarEscalas($plan, $escalas);
        });

        return redirect()->route('planes.index', ['sistema' => $plan->id_sistema])->with('success', 'Plan creado.');
    }

    public function edit(Plan $plan)
    {
        $sistemas = SistemaSaas::orderBy('nombre')->get();
        $plan->load('escalas');

        return view('planes.edit', compact('plan', 'sistemas'));
    }

    public function update(Request $request, Plan $plan)
    {
        [$data, $escalas] = $this->validar($request, $plan);

        DB::transaction(function () use ($plan, $data, $escalas) {
            $plan->update($data);
            $this->guardarEscalas($plan, $escalas);
        });

        return redirect()->route('planes.index', ['sistema' => $plan->id_sistema])
            ->with('success', 'Plan actualizado. Las suscripciones ya creadas conservan el precio que tenían.');
    }

    protected function guardarEscalas(Plan $plan, array $escalas): void
    {
        $plan->escalas()->delete();

        foreach ($escalas as $escala) {
            $plan->escalas()->create($escala);
        }
    }

    /** @return array{0: array, 1: array} [datos del plan, escalas] */
    protected function validar(Request $request, ?Plan $plan = null): array
    {
        // Las filas de escala totalmente vacías se ignoran
        $request->merge(['escalas' => array_values(array_filter(
            (array) $request->input('escalas', []),
            fn ($fila) => filled($fila['desde'] ?? null) || filled($fila['hasta'] ?? null) || filled($fila['precio'] ?? null)
        ))]);

        $data = $request->validate([
            'id_sistema' => 'required|exists:sistemas_saas,id_sistema',
            'codigo' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('planes', 'codigo')
                    ->where('id_sistema', $request->input('id_sistema'))
                    ->ignore($plan?->id_plan, 'id_plan'),
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:recurrente,unico',
            'duracion_meses' => 'nullable|integer|min:1|max:120',
            'meses_permitidos' => ['nullable', 'string', 'max:50', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/'],
            'precio' => 'required|numeric|min:0',
            'moneda' => 'required|string|size:3',
            'unidad_nombre' => 'nullable|string|max:50',
            'unidad_minima' => 'nullable|integer|min:1',
            'unidad_maxima' => 'nullable|integer|min:1|gte:unidad_minima',
            'escalas' => 'nullable|array|max:20',
            'escalas.*.desde' => 'required|integer|min:1',
            'escalas.*.hasta' => 'nullable|integer|min:1|gte:escalas.*.desde',
            'escalas.*.precio' => 'required|numeric|min:0',
        ], [
            'meses_permitidos.regex' => 'Escribí los meses separados por coma, por ejemplo: 1,3,6,12.',
            'escalas.*.hasta.gte' => 'En cada escala, "hasta" no puede ser menor que "desde".',
        ]);

        $escalas = collect($data['escalas'] ?? [])
            ->map(fn ($e) => [
                'desde' => (int) $e['desde'],
                'hasta' => filled($e['hasta'] ?? null) ? (int) $e['hasta'] : null,
                'precio' => $e['precio'],
            ])
            ->sortBy('desde')->values()->all();

        unset($data['escalas']);

        $data['moneda'] = strtoupper($data['moneda']);
        $data['cobro_por_unidad'] = $request->boolean('cobro_por_unidad');
        $data['activo'] = $request->boolean('activo');
        $data['unidad_minima'] = $data['unidad_minima'] ?? 1;

        // El débito automático cobra cada período solo: no tiene "elegir meses"
        $data['meses_permitidos'] = $data['tipo'] === 'recurrente'
            ? null
            : (filled($data['meses_permitidos'] ?? null) ? preg_replace('/\s+/', '', $data['meses_permitidos']) : null);

        if ($data['tipo'] === 'recurrente' && empty($data['duracion_meses'])) {
            $data['duracion_meses'] = 1;
        }

        if (! $data['cobro_por_unidad']) {
            $data['unidad_nombre'] = null;
            $data['unidad_maxima'] = null;
            $data['unidad_minima'] = 1;
            $escalas = [];
        }

        return [$data, $escalas];
    }
}
