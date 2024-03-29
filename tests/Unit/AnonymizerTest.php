<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Anonymizer;
use PHPUnit\Framework\TestCase;

class AnonymizerTest extends TestCase
{
    /**
     * @dataProvider emails
     */
    public function testEmail(string $has, string $want): void
    {
        $anonymizer = new Anonymizer();

        $this->assertEquals($want, $anonymizer->email($has));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testPhoneNumber(string $has, string $want): void
    {
        $anonymizer = new Anonymizer();

        $this->assertEquals($want, $anonymizer->phoneNumber($has));
    }

    public function emails(): array
    {
        return [
            [ '', '***' ],
            [ 'a@example.org', '*@example.org' ],
            [ 'john@example.org', '****@example.org' ],
            [ 'foobar@example.org', 'fo**ar@example.org' ],
            [ 'john.doe@example.org', 'jo****oe@example.org' ],
        ];
    }

    public function phoneNumbers(): array
    {
        return [
            [ '0', '****' ],
            [ '06', '****' ],
            [ '061', '**61' ],
            [ '0612', '**12' ],
            [ '06123', '***23' ],
            [ '061234', '****34' ],
            [ '0612345', '*****45' ],
            [ '06123456', '******56' ],
            [ '061234567', '*******67' ],
            [ '0612345678', '********78' ],
        ];
    }
}
