<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Code;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testIsExpired($expiredAt, $isExpired): void
    {
        $code = new Code([
            'code' => '123456',
            'expires_at' => $expiredAt
        ]);

        self::assertEquals($isExpired, $code->isExpired());
    }

    public function provider(): array
    {
        return [
            [time() - 1, true],
            [time() + 5, false],
            [(new Carbon('now - 1 hour'))->getTimestamp(), true],
            [(new Carbon('now + 5 minutes'))->getTimestamp(), false],
        ];
    }
}
