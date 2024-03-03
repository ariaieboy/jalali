<?php

namespace Ariaieboy\Jalali\Tests;

use Ariaieboy\Jalali\Jalali;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_jdate_function()
    {
        $this->assertTrue(function_exists('jalali'));

        $jdate = jalali('now');
        $this->assertTrue($jdate instanceof Jalali);
    }
}
