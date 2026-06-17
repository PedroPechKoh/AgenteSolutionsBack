<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quote;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoController extends Controller
{
    public function __construct()
    {
        // Inicializamos MercadoPago con el token de acceso
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_ACCESS_TOKEN'));
    }

    public function createPreference(Request $request, $id)
    {
        $quote = Quote::findOrFail($id);

        try {
            $client = new PreferenceClient();

            // Configurar URLs de retorno a nuestro frontend
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            $preference = $client->create([
                "items" => [
                    [
                        "id" => $quote->id,
                        "title" => "Cotización #" . ($quote->folio ?? $quote->id),
                        "description" => "Pago de servicio de mantenimiento/reparación",
                        "quantity" => 1,
                        "unit_price" => (float) $quote->total,
                        "currency_id" => "MXN"
                    ]
                ],
                "back_urls" => [
                    "success" => $frontendUrl . "/vista-cotizaciones?payment_status=success&quote_id=" . $quote->id,
                    "failure" => $frontendUrl . "/vista-cotizaciones?payment_status=failure&quote_id=" . $quote->id,
                    "pending" => $frontendUrl . "/vista-cotizaciones?payment_status=pending&quote_id=" . $quote->id
                ],
                "auto_return" => "approved", // Redirige automáticamente cuando el pago es aprobado
                "external_reference" => (string) $quote->id, // Para identificar el pago en el webhook
            ]);

            return response()->json([
                'id' => $preference->id,
                'init_point' => $preference->init_point, // URL para redirigir al usuario en producción
                'sandbox_init_point' => $preference->sandbox_init_point // URL para pruebas
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la preferencia de pago', 'details' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        // MercadoPago envía notificaciones de tipo "payment"
        if ($request->type === 'payment' && isset($request->data['id'])) {
            try {
                // Instanciar el cliente de pagos de MercadoPago para verificar el estado real del pago
                $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();
                $payment = $paymentClient->get($request->data['id']);

                if ($payment) {
                    // El external_reference es el ID de nuestra cotización
                    $quoteId = $payment->external_reference;
                    $quote = Quote::find($quoteId);

                    if ($quote) {
                        // Si el pago fue aprobado, marcamos la cotización como Pagado
                        if ($payment->status === 'approved') {
                            $quote->status = 'Pagado';
                            $quote->save();
                        } elseif ($payment->status === 'in_process') {
                            // Si el pago está en revisión (por ejemplo pago en efectivo OXXO pendiente)
                            $quote->status = 'Pago en Revisión';
                            $quote->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                // Loggear el error si falla la verificación
                \Log::error('Error verificando Webhook de MercadoPago: ' . $e->getMessage());
                return response()->json(['error' => 'Error processing webhook'], 500);
            }
        }

        // Siempre debemos responder con 200 OK a MercadoPago para que sepa que recibimos la notificación
        return response()->json(['status' => 'success'], 200);
    }
}
