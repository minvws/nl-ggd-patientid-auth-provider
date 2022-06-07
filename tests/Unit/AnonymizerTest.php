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
    public function testEmail(string $has, string $want)
    {
        $anonymizer = new Anonymizer();

        $this->assertEquals($want, $anonymizer->email($has));
    }

    /**
     * @dataProvider phonenrs
     */
    public function testPhoneNr(string $has, string $want)
    {
        $anonymizer = new Anonymizer();

        $this->assertEquals($want, $anonymizer->phoneNr($has));
    }

    public function emails()
    {
        return [
            [ 'a@example.org', '*@example.org' ],
            [ 'john@example.org', '****@example.org' ],
            [ 'foobar@example.org', 'fo**ar@example.org' ],
            [ 'john.doe@example.org', 'jo****oe@example.org' ],
        ];
    }

    public function phonenrs(): array
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
