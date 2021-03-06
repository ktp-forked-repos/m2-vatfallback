<?php
/**
 * Dutchento Vatfallback
 * Provides free VAT fallback mechanism
 * Copyright (C) 2018 Dutchento
 *
 * MIT license applies to this software
 */

namespace Dutchento\Vatfallback\Test\Unit\Service;

use Dutchento\Vatfallback\Service\CleanNumberString;

/**
 * Class CleanNumberStringTest
 * @package Dutchento\Vatfallback\Test\Unit\Service
 */
class CleanNumberStringTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataproviderStrippingCountry
     */
    public function testStrippingCountryAndChars($given, $expected)
    {
        $cleanNumber = new CleanNumberString();

        $this->assertEquals($expected, $cleanNumber->returnStrippedString($given));
    }

    public function dataproviderStrippingCountry()
    {
        return [ // given, expected
            ['NL163001688B01', '163001688B01'],
            ['NLNL163001688B01', 'NL163001688B01'],
            ['-NL163001688B01', 'NL163001688B01'],
            ['NL-16 300 16 88#B01', '163001688B01'],
            ['BE 0123.456.789', '0123456789'],
        ];
    }
}
