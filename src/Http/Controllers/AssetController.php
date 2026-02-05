<?php

namespace Kianisanaullah\TrafficSentinel\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function show(string $file)
    {
        $path = __DIR__ . '/../../../resources/assets/' . $file;

        if (! File::exists($path)) {
            abort(404);
        }

        $mime = File::mimeType($path) ?? 'application/octet-stream';

        return response(
            File::get($path),
            200,
            [
                'Content-Type'  => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }
}
