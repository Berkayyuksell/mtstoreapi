<?php

namespace App\Http\Controllers;

use App\Models\ZplLabelTemplate;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ZplPreviewController extends Controller
{
    /**
     * ZPL template'i Labelary API üzerinden PNG'ye dönüştürür ve akıtır.
     *
     * GET /admin/zpl-preview/{template}
     *
     * Labelary Docs: http://labelary.com/service.html
     *   POST http://api.labelary.com/v1/printers/{dpmm}/labels/{width}x{height}/{index}/
     *   Body (form): file=<ZPL>
     *   Response: image/png
     */
    public function show(ZplLabelTemplate $template): Response
    {
        $dpmm   = 8;    // 8 dpmm = 203 dpi (standart termal yazıcı)
        $width  = 4;    // inç
        $height = 3;    // inç

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'image/png'])
                ->asForm()
                ->post("http://api.labelary.com/v1/printers/{$dpmm}dpmm/labels/{$width}x{$height}/0/", [
                    'file' => $template->zpl_template,
                ]);

            if (! $response->successful()) {
                return $this->errorImage('Labelary API: HTTP ' . $response->status());
            }

            return response($response->body(), 200, [
                'Content-Type'  => 'image/png',
                'Cache-Control' => 'private, max-age=30',
            ]);
        } catch (\Throwable $e) {
            return $this->errorImage($e->getMessage());
        }
    }

    /**
     * API erişilemezse minimal bir 1×1 PNG döner ve hatayı header'a yazar.
     */
    private function errorImage(string $reason): Response
    {
        // 1×1 şeffaf PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return response($png, 200, [
            'Content-Type'    => 'image/png',
            'X-Preview-Error' => $reason,
        ]);
    }
}
