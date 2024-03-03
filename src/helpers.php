<?php

if (! function_exists('jalali')) {

    function jalali(?string $str = null): Ariaieboy\Jalali\Jalali
    {
        return \Ariaieboy\Jalali\Jalali::forge($str);
    }
}
