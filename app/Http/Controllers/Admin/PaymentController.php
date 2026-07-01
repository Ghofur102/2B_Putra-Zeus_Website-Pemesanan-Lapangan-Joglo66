<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessPaymentRequest;
use App\Services\Admin\PaymentService;
use App\Models\Booking;
use App\Http\Controllers\Traits\FieldAccessTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class PaymentController extends Controller
{
    use FieldAccessTrait;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function processPayment(ProcessPaymentRequest $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $booking = Booking::query()->find($request->booking_id);
            if (!$booking) {
                throw new HttpException(400, 'Booking not found.');
            }

            if (!$this->checkFieldAccess($request->user(), $booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Forbidden. Anda tidak memiliki akses untuk memproses pembayaran di lapangan ini.');
            }

            $result = $this->paymentService->process($request->validated());
            $payment = $result['payment'];
            $warning = $result['warning'];

            $data = [
                'success' => true,
                'message' => 'Payment processed successfully.',
                'data' => [
                    'payment_id'  => $payment->id,
                    'status'      => $payment->status,
                    'payment_url' => $payment->payment_url,
                ],
            ];

            if (!is_null($warning)) {
                $data['warning'] = $warning;
            }

        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Failed to process payment.',
                'error'   => $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }
}
