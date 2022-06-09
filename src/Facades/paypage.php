<?php


namespace Paytabscom\LaravelPaytabs\Facades;


use Illuminate\Support\Facades\Facade;

class Paypage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paypage';
    }

}
