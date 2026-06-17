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
            $frontendUrl = $request->header('origin') ?? env('FRONTEND_URL', 'https://agentesolutions-production.up.railway.app');
            // Asegurar que no termine en slash
            $frontendUrl = rtrim($frontendUrl, '/');

            $total = (float) str_replace(['$', ',', ' '], '', $quote->estimated_amount);

            $preference = $client->create([
                "items" => [
                    [
                        "id" => (string) $quote->id,
                        "title" => "Cotización #" . ($quote->folio ?? $quote->id),
                        "description" => "Pago de servicio de mantenimiento/reparación",
                        "quantity" => 1,
                        "unit_price" => $total,
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
            
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            return response()->json([
                'error' => 'Error de la API de MercadoPago', 
                'details' => $apiResponse ? $apiResponse->getContent() : $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        // MP puede enviar 'type' o 'topic', y el ID puede estar en 'data.id' o 'id'
        $type = $request->input('type') ?? $request->input('topic');
        $action = $request->input('action');
        $dataId = $request->input('data.id') ?? $request->input('id');

        // Considerar válido si es payment, o si action es payment.created
        if (($type === 'payment' || $action === 'payment.created') && $dataId) {
            try {
                // Instanciar el cliente de pagos de MercadoPago para verificar el estado real del pago
                $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();
                $payment = $paymentClient->get($dataId);

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
