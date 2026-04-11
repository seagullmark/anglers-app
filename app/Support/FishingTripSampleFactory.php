<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class FishingTripSampleFactory
{
    private const RIVERS = [
        '歴舟川',
        '多摩川',
        '天塩川',
        '千歳川',
        '十勝川',
        '尻別川',
        '渚滑川',
        '豊平川',
    ];

    private const POINTS = [
        'いつもの',
        '橋脚下流',
        '流れ込み',
        '瀬肩',
        '深み手前',
        '護岸際',
        '中洲まわり',
        '朝マズメの本流筋',
    ];

    private const TACKLES = [
        'フライ',
        'スピニング ML',
        'ベイト M',
        'UL スピニング',
        'シングルハンド 5wt',
        'ダブルハンド 7wt',
    ];

    private const MEMOS = [
        '朝イチは反応が薄く、流れが緩んだタイミングでヒット。',
        '風が強かったので軽めのルアーは流されやすかった。',
        '瀬の終わりで反応。足元まで丁寧に引いたら出た。',
        '濁りが入っていたので強めのシルエットが良かった。',
        '日が落ちてから反応が上がった。',
        '同行者にも同じ筋で反応あり。',
    ];

    private const PALETTES = [
        [[15, 23, 42], [56, 189, 248], [241, 245, 249]],
        [[8, 47, 73], [34, 197, 94], [240, 253, 244]],
        [[67, 20, 7], [251, 146, 60], [255, 247, 237]],
        [[49, 46, 129], [129, 140, 248], [238, 242, 255]],
        [[69, 10, 10], [248, 113, 113], [254, 242, 242]],
    ];

    public function makeTripAttributes(string $userId, int $index, ?Carbon $now = null): array
    {
        $now ??= now();

        $tripDate = $now->copy()->subDays($index);
        $startAt = $tripDate->copy()->setTime(5 + ($index % 8), [0, 15, 30, 45][$index % 4]);
        $endAt = $startAt->copy()->addMinutes(90 + (($index * 37) % 210));
        $timestamp = $now->copy()->subMinutes($index * 10);

        return [
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'trip_date' => $tripDate->copy()->startOfDay(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'river_name' => self::RIVERS[$index % count(self::RIVERS)],
            'point_name' => self::POINTS[$index % count(self::POINTS)],
            'tackle_name' => self::TACKLES[$index % count(self::TACKLES)],
            'memo' => self::MEMOS[$index % count(self::MEMOS)],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    public function makePhotoAttributes(string $tripId, int $tripIndex, int $photoIndex, ?Carbon $now = null): array
    {
        $now ??= now();
        $timestamp = $now->copy()->subMinutes(($tripIndex * 10) + $photoIndex);

        return [
            'id' => (string) Str::uuid(),
            'fishing_trip_id' => $tripId,
            'caption' => sprintf('Sample photo %d for trip %d', $photoIndex + 1, $tripIndex + 1),
            'sort_order' => $photoIndex + 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    public function createPlaceholderImage(int $tripIndex, int $photoIndex, string $riverName): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('GD is required to generate sample trip images.');
        }

        $width = 1200;
        $height = 900;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('Failed to initialize a sample image canvas.');
        }

        [$backgroundColor, $accentColor, $textColor] = self::PALETTES[($tripIndex + $photoIndex) % count(self::PALETTES)];

        $background = imagecolorallocate($image, ...$backgroundColor);
        $accent = imagecolorallocate($image, ...$accentColor);
        $text = imagecolorallocate($image, ...$textColor);
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 90);

        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 0, 0, $width, 180, $accent);
        imagefilledellipse($image, 930, 240, 360, 360, $overlay);
        imagefilledellipse($image, 260, 720, 520, 520, $overlay);
        imageline($image, 0, 620, $width, 480, $accent);
        imageline($image, 0, 700, $width, 560, $accent);

        imagestring($image, 5, 48, 52, 'Fishing Trip Sample', $text);
        imagestring($image, 5, 48, 230, $riverName, $text);
        imagestring($image, 4, 48, 278, sprintf('Trip %02d / Photo %02d', $tripIndex + 1, $photoIndex + 1), $text);
        imagestring($image, 3, 48, 326, now()->format('Y-m-d H:i'), $text);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'fm-trip-photo-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Failed to allocate a temporary file for the sample image.');
        }

        $path = $temporaryPath . '.jpg';

        if (! rename($temporaryPath, $path)) {
            @unlink($temporaryPath);

            throw new RuntimeException('Failed to prepare the sample image path.');
        }

        imagejpeg($image, $path, 88);

        return $path;
    }
}
