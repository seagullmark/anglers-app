<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ContainerController extends Controller
{
    protected $mimes = array(
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        // 'svg'  => 'image/svg+xml',
    );

    public function getImage(Request $request)
    {
        // 認証はDBアクセス側で担保（FMS: AuthenticatedStream=1 前提）。route の auth とは別。
        $path = Crypt::decryptString($request->route('path'));
        $info = pathinfo($path);
        $ext = explode('?', strtolower(@$info['extension']));
        $ext = $ext[0];

        // 拡張子が見つからなかった場合の制御
        if (empty($ext) || !array_key_exists($ext, $this->mimes)) {
            return response()
                ->json(['error' => 'Unsupported file type.'], 400)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        // クッキーの管理のために CookieJar を作成
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([], '');

        // Http ファサードを使用
        $response = Http::withOptions([
            'follow_redirects' => true, // リダイレクトを追従
            'cookies' => $cookieJar, // CookieJar を指定
            'timeout' => 10,
            'connect_timeout' => 5,
        ])->get($path);

        if ($response->failed()) {
            return response()
                ->json(['error' => 'Upstream request failed.'], 502)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        $maxBytes = 5 * 1024 * 1024;
        $contentLength = $response->header('Content-Length');
        if (is_numeric($contentLength) && (int) $contentLength > $maxBytes) {
            return response()
                ->json(['error' => 'File too large.'], 413)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        $body = $response->body();
        if (strlen($body) > $maxBytes) {
            return response()
                ->json(['error' => 'File too large.'], 413)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        $allowedMimes = array_values($this->mimes);
        $rawContentType = strtolower(trim(explode(';', $response->header('Content-Type') ?? '')[0]));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($body);

        if (!in_array($detected, $allowedMimes, true)) {
            return response()
                ->json(['error' => 'Unsupported content type.'], 415)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        if ($rawContentType !== '' && $rawContentType !== $detected) {
            return response()
                ->json(['error' => 'Content type mismatch.'], 415)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        // Content-Type を設定してレスポンスを返す
        $content_type = $detected;
        return response($body, 200)
            ->header('Content-Type', $content_type)
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
