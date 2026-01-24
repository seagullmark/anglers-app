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
        ])->get($path);

        if ($response->failed()) {
            return response()
                ->json(['error' => $response->body()], 500)
                ->header('X-Content-Type-Options', 'nosniff');
        }

        // Content-Type を設定してレスポンスを返す
        $content_type = $this->mimes[$ext];
        return response($response->body(), 200)
            ->header('Content-Type', $content_type)
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
