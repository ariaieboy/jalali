<?php

if (! function_exists('jalali')) {

    function jalali(string $str): Ariaieboy\Jalali\Jalali
    {
        return \Ariaieboy\Jalali\Jalali::forge($str);
    }
}
