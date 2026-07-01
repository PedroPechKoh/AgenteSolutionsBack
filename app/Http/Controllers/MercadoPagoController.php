<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\MercadoPagoPaymentReceived;
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
        $quote = Quote::with(['service.property.client', 'workOrder.property.client'])->findOrFail($id);

        try {
            $client = new PreferenceClient();

            // Configurar URLs de retorno a nuestro frontend
            $frontendUrl = $request->header('origin') ?? env('FRONTEND_URL', 'https://agentesolutions-production.up.railway.app');
            // Asegurar que no termine en slash
            $frontendUrl = rtrim($frontendUrl, '/');

            $totalBase = (float) str_replace(['$', ',', ' '], '', $quote->estimated_amount);

            // Determinar etapa de pago
            $stage = $request->input('payment_stage', 'full'); // 'advance', 'remaining', 'full'

            if ($stage === 'advance') {
                $total = round($totalBase * 0.60, 2);
                $stageLabel = 'Anticipo (60%)';
                // Guardar montos calculados si aun no existen
                if (!$quote->advance_amount) {
                    $quote->advance_amount   = $total;
                    $quote->remaining_amount = round($totalBase * 0.40, 2);
                    $quote->payment_scheme   = 'split';
                    $quote->save();
                }
            } elseif ($stage === 'remaining') {
                $total = round($totalBase * 0.40, 2);
                $stageLabel = 'Liquidación Finiquito (40%)';
            } else {
                $total = $totalBase;
                $stageLabel = 'Pago Total';
            }

            $clientModel = $quote->service?->property?->client ?? $quote->workOrder?->property?->client ?? null;
            $propertyName = $quote->service?->property?->property_name ?? $quote->workOrder?->property?->property_name ?? 'N/A';
            $clientName = $clientModel->name ?? 'Cliente';
            $title = "{$stageLabel} - Cotización #{$quote->id} / {$propertyName}";

            $preferenceParams = [
                "items" => [
                    [
                        "id"          => (string) $quote->id,
                        "title"       => $title,
                        "description" => "Pago de servicio de mantenimiento/reparación",
                        "quantity"    => 1,
                        "unit_price"  => $total,
                        "currency_id" => "MXN"
                    ]
                ],
                "back_urls" => [
                    "success" => $frontendUrl . "/vista-cotizaciones?payment_status=success&quote_id=" . $quote->id . "&stage={$stage}",
                    "failure" => $frontendUrl . "/vista-cotizaciones?payment_status=failure&quote_id=" . $quote->id,
                    "pending" => $frontendUrl . "/vista-cotizaciones?payment_status=pending&quote_id=" . $quote->id
                ],
                "auto_return"        => "approved",
                "external_reference" => $quote->id . '|' . $stage, // Include stage so webhook knows which payment it is
                "notification_url"   => env('APP_URL', 'https://agentesolutionsback-production.up.railway.app') . "/api/mercadopago/webhook"
            ];

            if ($request->user() && $request->user()->email) {
                $preferenceParams["payer"] = [
                    "email" => $request->user()->email,
                    "name" => $request->user()->name ?? 'Cliente'
                ];
            }

            $preference = $client->create($preferenceParams);

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

    /**
     * Extrae los datos relevantes del pago para guardar como comprobante.
     */
    private function buildPaymentData($payment): array
    {
        $card = $payment->card ?? null;
        $paymentMethodId = $payment->payment_method_id ?? 'desconocido';
        $paymentTypeId = $payment->payment_type_id ?? 'desconocido';
        $lastFourDigits = $card ? ($card->last_four_digits ?? null) : null;
        $cardHolder = $card ? ($card->cardholder->name ?? null) : null;
        $brand = $payment->payment_method_id ?? null;

        // Formatear tipo de pago amigable
        $paymentLabel = match($paymentTypeId) {
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card'  => 'Tarjeta de Débito',
            'account_money' => 'Saldo MercadoPago',
            'bank_transfer' => 'Transferencia Bancaria',
            'ticket' => 'Efectivo (OXXO/CoDi)',
            default => ucfirst($paymentTypeId)
        };

        return [
            'mp_payment_id'     => $payment->id,
            'status'            => $payment->status,
            'amount'            => $payment->transaction_amount,
            'currency'          => $payment->currency_id ?? 'MXN',
            'payment_type'      => $paymentLabel,
            'payment_method'    => strtoupper($brand ?? ''),
            'last_four_digits'  => $lastFourDigits,
            'card_holder'       => $cardHolder,
            'payer_email'       => $payment->payer->email ?? null,
            'date_approved'     => $payment->date_approved ?? null,
            'date_created'      => $payment->date_created ?? null,
        ];
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
                        // Si el pago fue aprobado, marcamos la cotización según la etapa
                        if ($payment->status === 'approved') {
                            // Separar quote_id y stage del external_reference
                            $refParts = explode('|', $payment->external_reference ?? '');
                            $stage    = $refParts[1] ?? 'full';

                            $paymentData = $this->buildPaymentData($payment);

                            if ($stage === 'advance') {
                                $quote->advance_paid    = true;
                                $quote->advance_paid_at = now();
                                $quote->advance_mp_data = $paymentData;
                                $quote->status          = 'Anticipo Pagado (60%)';
                            } elseif ($stage === 'remaining') {
                                $quote->remaining_paid    = true;
                                $quote->remaining_paid_at = now();
                                $quote->remaining_mp_data = $paymentData;
                                $quote->status            = 'Pagado';
                            } else {
                                // Pago total (full)
                                $quote->mp_payment_data = $paymentData;
                                $quote->status          = 'Pagado';
                            }

                            $quote->save();

                            // Notificar al Admin
                            $admin = User::where('role_id', 1)->first();
                            if ($admin) {
                                $admin->notify(new MercadoPagoPaymentReceived($quote));
                            }
                            // Notificar al Cliente
                            if ($quote->cliente_user_id) {
                                $clienteUser = User::find($quote->cliente_user_id);
                                if ($clienteUser) {
                                    $clienteUser->notify(new MercadoPagoPaymentReceived($quote));
                                }
                            }
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

    public function verifyPayment(Request $request)
    {
        $quoteId = $request->input('quote_id');
        if (!$quoteId) {
            return response()->json(['error' => 'quote_id is required'], 400);
        }

        $quote = Quote::find($quoteId);
        if (!$quote) {
            return response()->json(['error' => 'Quote not found'], 404);
        }

        // Si ya está pagado, retornamos success de inmediato
        if ($quote->status === 'Pagado') {
            return response()->json(['status' => 'success', 'already_paid' => true, 'mp_payment_data' => $quote->mp_payment_data]);
        }

        try {
            $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();

            $searchRequest = new \MercadoPago\Net\MPSearchRequest(30, 0, [
                "external_reference" => (string) $quote->id,
                "status" => "approved"
            ]);

            $searchResult = $paymentClient->search($searchRequest);

            if ($searchResult && !empty($searchResult->results)) {
                $payment = $searchResult->results[0];

                $quote->status = 'Pagado';
                $quote->mp_payment_data = $this->buildPaymentData($payment);
                $quote->save();

                // Notificar al Admin
                $admin = User::where('role_id', 1)->first();
                if ($admin) {
                    $admin->notify(new MercadoPagoPaymentReceived($quote));
                }

                // Notificar al Cliente navegando relaciones
                $quote->load(['service.property.client', 'workOrder.property.client']);
                $cliente = $quote->service?->property?->client ?? $quote->workOrder?->property?->client ?? null;
                if ($cliente && $cliente->user_id) {
                    $clienteUser = User::find($cliente->user_id);
                    if ($clienteUser) {
                        $clienteUser->notify(new MercadoPagoPaymentReceived($quote));
                    }
                }

                return response()->json(['status' => 'success', 'verified' => true, 'mp_payment_data' => $quote->mp_payment_data]);
            }

            return response()->json(['status' => 'pending']);

        } catch (\Exception $e) {
            \Log::error('Error verificando pago manualmente: ' . $e->getMessage());
            return response()->json(['status' => 'pending', 'debug' => $e->getMessage()]);
        }
    }
}
