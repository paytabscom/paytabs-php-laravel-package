<?php


namespace  Paytabscom\Laravel_paytabs;
use Illuminate\Support\Facades\Facade;

class paypage extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'paypage';
    }

}
