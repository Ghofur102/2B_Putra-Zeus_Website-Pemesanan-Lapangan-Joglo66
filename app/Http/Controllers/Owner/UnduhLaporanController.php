<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DownloadReportRequest;
use App\Services\Owner\UnduhLaporanService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class UnduhLaporanController extends Controller
{
    protected UnduhLaporanService $unduhLaporanService;

    public function __construct(UnduhLaporanService $unduhLaporanService)
    {
        $this->unduhLaporanService = $unduhLaporanService;
    }

    public function download(DownloadReportRequest $request): Response
    {
        $status = 200;
        $headers = [];
        $content = '';

        try {
            $result = $this->unduhLaporanService->generatePdfReport($request->validated());

            $content = $result['content'];
            $headers = $result['headers'];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $content = $e->getMessage();
            $headers = ['Content-Type' => 'text/plain'];
        } catch (Throwable $e) {
            $status = 500;
            $content = 'Terjadi kesalahan internal server: ' . $e->getMessage();
            $headers = ['Content-Type' => 'text/plain'];
        }

        return response($content, $status, $headers);
    }
}
