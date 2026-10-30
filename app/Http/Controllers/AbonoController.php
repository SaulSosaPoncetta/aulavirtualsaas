<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Institucion;
use App\Models\Pago;
use App\Services\AbonoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AbonoController extends Controller
{
    public function __construct(protected AbonoService $precios)
    {
    }

    // ===================== Abono unipersonal (docente) =====================

    public function mio(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403, 'El abono unipersonal es para docentes.');

        $abono = Abono::deUsuario($user)->load('pagos');
        $plan  = $this->precios->planUnipersonal();

        return view('abonos.mio', [
            'abono'         => $abono,
            'plan'          => $plan,
            'meses'         => $this->precios->opcionesMeses(),
            'transferencia' => config('abonos.transferencia'),
            'pendiente'     => $abono->pagos->firstWhere('estado', 'pendiente'),
            'instituciones' => $user->institucionesDocente()->with('abono')->get()->unique('id')->values(),
        ]);
    }

    public function pagarMio(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403);

        $abono = Abono::deUsuario($user);
        $datos = $this->validarAviso($request);

        $importe = $this->precios->cotizarRenovacion($abono, (int) $datos['meses']);
        abort_if($importe === null, 422, 'Todavía no hay un precio cargado para el abono unipersonal.');

        $this->crearPagoManual($request, $abono, [
            'concepto' => 'renovacion',
            'meses'    => (int) $datos['meses'],
            'importe'  => $importe,
        ]);

        return redirect()->route('abonos.mio')
            ->with('exito', 'Recibimos tu aviso de pago. Cuando se confirme la acreditación, el abono se activa.');
    }

    // ===================== Abono institucional =====================

    public function institucion(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $abono = Abono::deInstitucion($institucion)->load('pagos');

        return view('abonos.institucion', [
            'institucion'   => $institucion,
            'abono'         => $abono,
            'usados'        => $abono->docentesUsados(),
            'escalas'       => $this->precios->escalasInstitucionales(),
            'meses'         => $this->precios->opcionesMeses(),
            'transferencia' => config('abonos.transferencia'),
            'pendiente'     => $abono->pagos->firstWhere('estado', 'pendiente'),
        ]);
    }

    /** Cotización en vivo (para el formulario). */
    public function cotizar(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $abono = Abono::deInstitucion($institucion);
        $cupo  = max(1, (int) $request->query('cupo', 1));
        $meses = max(1, (int) $request->query('meses', 1));

        $importe = $request->query('concepto') === 'ampliacion'
            ? $this->precios->cotizarAmpliacion($abono, $cupo)
            : $this->precios->cotizarRenovacion($abono, $meses, $cupo);

        return response()->json([
            'importe'    => $importe,
            'texto'      => $importe !== null ? \App\Models\Plan::pesos($importe) : '—',
            'porDocente' => ($p = $this->precios->precioPorDocente($cupo)) !== null ? \App\Models\Plan::pesos($p) : '—',
        ]);
    }

    public function pagarInstitucion(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $abono    = Abono::deInstitucion($institucion);
        $usados   = $abono->docentesUsados();
        $concepto = $request->input('concepto') === 'ampliacion' ? 'ampliacion' : 'renovacion';
        $datos    = $this->validarAviso($request, true);
        $cupo     = (int) $datos['cupo_docentes'];

        if ($cupo < $usados) {
            return back()->withInput()->with('error', "Ya hay {$usados} docentes en la institución: el cupo no puede ser menor.");
        }

        if ($concepto === 'ampliacion') {
            $importe = $this->precios->cotizarAmpliacion($abono, $cupo);
            if ($importe === null) {
                return back()->withInput()->with('error', 'Para ampliar, el abono tiene que estar activo y el nuevo cupo ser mayor al actual.');
            }
            $meses = 0;
        } else {
            $meses   = (int) $datos['meses'];
            $importe = $this->precios->cotizarRenovacion($abono, $meses, $cupo);
            abort_if($importe === null, 422, 'No hay una escala de precios para esa cantidad de docentes.');
        }

        $this->crearPagoManual($request, $abono, [
            'concepto'      => $concepto,
            'meses'         => $meses,
            'cupo_docentes' => $cupo,
            'importe'       => $importe,
        ]);

        return redirect()->route('abonos.institucion', $institucion)
            ->with('exito', 'Recibimos el aviso de pago. Cuando se confirme la acreditación, el abono se actualiza.');
    }

    // ===================== Comunes =====================

    public function cancelar(Request $request, Pago $pago)
    {
        $this->autorizarPago($request, $pago);
        abort_unless($pago->estado === 'pendiente', 422, 'Solo se pueden cancelar pagos pendientes.');

        $pago->update(['estado' => 'cancelado']);

        return back()->with('exito', 'Aviso de pago cancelado.');
    }

    public function comprobante(Request $request, Pago $pago)
    {
        $this->autorizarPago($request, $pago);
        abort_unless($pago->comprobante_path && Storage::disk('local')->exists($pago->comprobante_path), 404);

        return Storage::disk('local')->response($pago->comprobante_path);
    }

    protected function autorizarPago(Request $request, Pago $pago): void
    {
        $user  = $request->user();
        $abono = $pago->abono;

        $ok = $user->esSuperadmin()
            || ($abono->user_id && $abono->user_id == $user->id)
            || ($abono->institucion_id && $abono->institucion->esAdmin($user));

        abort_unless($ok, 403);
    }

    protected function validarAviso(Request $request, bool $institucional = false): array
    {
        $reglas = [
            'meses'         => 'required_unless:concepto,ampliacion|nullable|integer|in:' . implode(',', $this->precios->opcionesMeses()),
            'metodo'        => 'required|in:transferencia,efectivo,otro',
            'comprobante'   => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'observaciones' => 'nullable|string|max:1000',
        ];

        if ($institucional) {
            $reglas['cupo_docentes'] = 'required|integer|min:1|max:' . config('abonos.cupo_maximo', 500);
        }

        return $request->validate($reglas, [
            'comprobante.mimes' => 'El comprobante tiene que ser PDF o imagen.',
        ], [
            'cupo_docentes' => 'cantidad de docentes',
            'metodo'        => 'forma de pago',
        ]);
    }

    protected function crearPagoManual(Request $request, Abono $abono, array $datos): Pago
    {
        // Un solo aviso pendiente a la vez
        $abono->pagos()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);

        $path = $request->hasFile('comprobante')
            ? $request->file('comprobante')->store("comprobantes/{$abono->id}", 'local')
            : null;

        return $abono->pagos()->create($datos + [
            'metodo'           => $request->input('metodo'),
            'estado'           => 'pendiente',
            'comprobante_path' => $path,
            'observaciones'    => $request->input('observaciones'),
            'creado_por'       => $request->user()->id,
        ]);
    }
}
