<?php

namespace App\Http\Controllers\Tenant\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Payment\DuitkuCallbackRequest;
use App\Services\Tenant\Payment\TenantDuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;
use Throwable;

class DuitkuController extends Controller
{
    protected TenantDuitkuService $duitkuService;

    public function __construct(TenantDuitkuService $duitkuService)
    {
        $this->duitkuService = $duitkuService;
    }

    public function callback(DuitkuCallbackRequest $request): JsonResponse
    {
        $statusCode = 200;
        $data = ['status' => 'Success'];

        try {
            $this->duitkuService->processWebhookCallback($request->validated());
        } catch (InvalidArgumentException $e) {
            $statusCode = $e->getCode() ?: 400;
            $data = ['status' => $e->getMessage()];
        } catch (UnexpectedValueException $e) {
            $statusCode = $e->getCode() ?: 404;
            $data = ['status' => $e->getMessage()];
        } catch (Throwable $e) {
            Log::error('Ada Error di Controller Callback: ' . $e->getMessage());
            $statusCode = 500;
            $data = ['status' => 'Internal Server Error'];
        }

        return response()->json($data, $statusCode);
    }
}
