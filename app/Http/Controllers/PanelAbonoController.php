<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Institucion;
use App\Models\User;
use App\Services\PanelPagos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/panel/abono — aviso firmado del Panel de Gestión cuando cambia un abono
 * (pago acreditado, vencimiento, suspensión, ampliación de cupo...).
 *
 * Seguridad: se verifica el HMAC-SHA256 del cuerpo crudo con el secret compartido.
 * Idempotente: si llega dos veces el mismo aviso, o uno más viejo que el ya aplicado, no rompe nada.
 */
class PanelAbonoController extends Controller
{
    public function recibir(Request $request)
    {
        $cuerpo = $request->getContent();

        if (! PanelPagos::firmaValida($cuerpo, $request->header('X-MiGestion-Signature'))) {
            return response()->json(['ok' => false, 'error' => 'firma inválida'], 401);
        }

        $d = json_decode($cuerpo, true);

        if (! is_array($d) || ($d['evento'] ?? null) !== 'abono.actualizado') {
            return response()->json(['ok' => false, 'error' => 'evento no soportado'], 422);
        }

        // Anti-repetición: avisos muy viejos que se vuelven a mandar no se aceptan
        $enviado = ! empty($d['enviado_en']) ? rescue(fn () => Carbon::parse($d['enviado_en']), null, false) : null;
        if (! $enviado) {
            return response()->json(['ok' => false, 'error' => 'enviado_en inválido'], 422);
        }

        if (! in_array($d['estado'] ?? null, ['activa', 'vencida', 'suspendida', 'cancelada', 'pendiente'], true)) {
            return response()->json(['ok' => false, 'error' => 'estado inválido'], 422);
        }

        $abono = $this->buscarAbono($d['referencia_externa'] ?? null);

        if (! $abono) {
            Log::warning('Aviso del Panel con referencia desconocida', ['ref' => $d['referencia_externa'] ?? null]);

            return response()->json(['ok' => false, 'error' => 'referencia desconocida'], 404);
        }

        // Un aviso más viejo que el último aplicado se ignora (los avisos pueden llegar desordenados)
        if ($abono->panel_sync_at && $enviado->lt($abono->panel_sync_at)) {
            return response()->json(['ok' => true, 'ignorado' => 'aviso anterior al último aplicado']);
        }

        $abono->aplicarDesdePanel($d);
        $abono->refresh();

        return response()->json([
            'ok'            => true,
            'estado_local'  => $abono->estado,
            'vence_el'      => $abono->vence_el?->toDateString(),
            'cupo_docentes' => $abono->cupo_docentes,
        ]);
    }

    protected function buscarAbono(?string $ref): ?Abono
    {
        $partes = PanelPagos::leerReferencia($ref);
        if (! $partes) {
            return null;
        }

        [$tipo, $id] = $partes;

        if ($tipo === 'u') {
            $user = User::find($id);

            return $user ? Abono::deUsuario($user) : null;
        }

        $institucion = Institucion::find($id);

        return $institucion ? Abono::deInstitucion($institucion) : null;
    }
}
