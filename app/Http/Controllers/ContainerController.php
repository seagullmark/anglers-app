<?php

namespace App\Http\Controllers;

use App\Models\FishingTrip;
use App\Models\FishingTripPhoto;
use App\Models\UserPhoto;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
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

    public function showUserPhoto(Request $request, string $user_photo)
    {
        $photo = UserPhoto::query()
            ->where('id', '==', $user_photo)
            ->firstOrFail();

        $this->authorize('view', $photo);

        return $this->proxyImage(
            $this->resolveContainerPath($photo, ['thumbnail', 'photo']),
        );
    }

    public function showFishingTripPhoto(Request $request, string $fishing_trip_photo)
    {
        $photo = FishingTripPhoto::query()
            ->where('id', '==', $fishing_trip_photo)
            ->firstOrFail();

        $trip = FishingTrip::query()
            ->where('id', '==', $photo->fishing_trip_id)
            ->firstOrFail();

        $this->authorize('view', $trip);

        return $this->proxyImage(
            $this->resolveContainerPath($photo, ['image']),
        );
    }

    private function proxyImage(?string $path)
    {
        if (blank($path)) {
            return response()
                ->json(['error' => 'Image not available.'], 404)
                ->header('X-Content-Type-Options', 'nosniff');
        }

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
        /** @var Response $response */
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

    private function resolveContainerPath(object $model, array $fields): ?string
    {
        foreach ($fields as $field) {
            $path = $model->getRawOriginal($field) ?? $model->getAttributeFromArray($field);

            if (filled($path)) {
                return $path;
            }
        }

        return null;
    }
}
