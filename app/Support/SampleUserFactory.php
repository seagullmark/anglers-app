<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class SampleUserFactory
{
    public function makeUserAttributes(
        int $sequence,
        string $password,
        ?Carbon $now = null,
        string $emailPrefix = 'sample-user',
        string $emailDomain = 'example.test',
        string $namePrefix = 'Sample User',
    ): array {
        $now ??= now();

        return [
            'name' => sprintf('%s %02d', $namePrefix, $sequence),
            'email' => sprintf(
                '%s-%s-%02d@%s',
                $emailPrefix,
                $now->format('YmdHis'),
                $sequence,
                $emailDomain,
            ),
            'password' => $password,
        ];
    }
}
