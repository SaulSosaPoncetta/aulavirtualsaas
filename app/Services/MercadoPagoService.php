<?php

namespace App\Services;

use App\Models\Pago;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Integración con Mercado Pago Checkout Pro usando la API REST (sin SDK).
 */
class MercadoPagoService
{
    public static function habilitado(): bool
    {
        return filled(config('mercadopago.access_token'));
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl(config('mercadopago.api'))
            ->withToken(config('mercadopago.access_token'))
            ->acceptJson()
            ->timeout(config('mercadopago.timeout', 20));
    }

    /**
     * Crea la preferencia de pago y devuelve la URL a la que hay que mandar al cliente.
     */
    public function crearPreferencia(Pago $pago, string $titulo, ?string $email): string
    {
        $esHttps = Str::startsWith(config('app.url'), 'https://');

        $datos = [
            'items' => [[
                'id'          => 'abono-' . $pago->abono_id,
                'title'       => $titulo,
                'description' => $pago->detalle,
                'quantity'    => 1,
                'currency_id' => 'ARS',
                'unit_price'  => round((float) $pago->importe, 2),
            ]],
            'external_reference'   => 'pago-' . $pago->id,
            'statement_descriptor' => mb_substr(config('mercadopago.statement'), 0, 13),
            'back_urls'            => [
                'success' => route('mp.retorno'),
                'pending' => route('mp.retorno'),
                'failure' => route('mp.retorno'),
            ],
            'expires' => true,
            'expiration_date_to' => now('America/Argentina/Buenos_Aires')->addDays(3)->format('Y-m-d\TH:i:s.vP'),
        ];

        if ($email) {
            $datos['payer'] = ['email' => $email];
        }

        // Mercado Pago solo acepta auto_return y webhooks con URLs públicas https
        if ($esHttps) {
            $datos['auto_return']      = 'approved';
            $datos['notification_url'] = route('mp.webhook');
        }

        $resp = $this->http()->post('/checkout/preferences', $datos);

        if (! $resp->successful()) {
            Log::error('Mercado Pago: error al crear preferencia', ['pago' => $pago->id, 'status' => $resp->status(), 'body' => $resp->body()]);
            throw new RuntimeException('No se pudo iniciar el pago con Mercado Pago (' . $resp->status() . ').');
        }

        $pago->update(['mp_preference_id' => $resp->json('id')]);

        // Con credenciales de prueba se usa el link de sandbox
        $esPrueba = Str::startsWith(config('mercadopago.access_token'), 'TEST-');

        return $esPrueba
            ? ($resp->json('sandbox_init_point') ?? $resp->json('init_point'))
            : $resp->json('init_point');
    }

    /**
     * Consulta un pago en Mercado Pago y actualiza el pago local.
     * Si está aprobado y el importe coincide, activa el abono.
     */
    public function procesarPago(string $paymentId): ?Pago
    {
        $resp = $this->http()->get('/v1/payments/' . urlencode($paymentId));

        if (! $resp->successful()) {
            Log::warning('Mercado Pago: no se pudo consultar el pago', ['payment_id' => $paymentId, 'status' => $resp->status()]);

            return null;
        }

        $mp  = $resp->json();
        $ref = (string) ($mp['external_reference'] ?? '');

        if (! Str::startsWith($ref, 'pago-')) {
            return null;
        }

        $pago = Pago::find((int) Str::after($ref, 'pago-'));
        if (! $pago || $pago->metodo !== 'mercadopago') {
            return null;
        }

        $estado = $mp['status'] ?? null;

        $pago->update([
            'mp_payment_id' => (string) $mp['id'],
            'mp_status'     => $estado . (isset($mp['status_detail']) ? ' / ' . $mp['status_detail'] : ''),
        ]);

        if ($pago->estado === 'aprobado') {
            return $pago; // ya estaba acreditado
        }

        if ($estado === 'approved') {
            $cobrado = (float) ($mp['transaction_amount'] ?? 0);

            if ($cobrado + 1 < (float) $pago->importe) {
                Log::warning('Mercado Pago: importe menor al esperado', ['pago' => $pago->id, 'cobrado' => $cobrado, 'esperado' => $pago->importe]);
                $pago->update(['observaciones' => trim($pago->observaciones . "\nImporte cobrado por MP ($cobrado) menor al esperado: revisar.")]);

                return $pago;
            }

            // Se acredita aunque el aviso estuviera cancelado: el dinero entró
            $pago->update(['estado' => 'pendiente']);
            $pago->abono->aplicarPago($pago);

            return $pago->fresh();
        }

        if (in_array($estado, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) && $pago->estado === 'pendiente') {
            $pago->update(['estado' => $estado === 'rejected' ? 'rechazado' : 'cancelado']);
        }

        return $pago;
    }

    /**
     * Valida la firma del webhook (cabecera x-signature) con la clave secreta.
     * Si no hay clave configurada, no valida (igual se consulta el pago a la API, que es la fuente de verdad).
     */
    public function firmaValida(Request $request): bool
    {
        $secreto = config('mercadopago.webhook_secret');
        if (! $secreto) {
            return true;
        }

        $firma     = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');
        $dataId    = (string) ($request->query('data_id') ?? $request->input('data.id'));

        $partes = [];
        foreach (explode(',', $firma) as $par) {
            [$k, $v] = array_pad(explode('=', trim($par), 2), 2, null);
            $partes[$k] = $v;
        }

        if (empty($partes['ts']) || empty($partes['v1'])) {
            return false;
        }

        $manifiesto = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $partes['ts'] . ';';

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $partes['v1']);
    }
}
