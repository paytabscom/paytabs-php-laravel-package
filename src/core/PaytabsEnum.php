<?php

namespace Paytabscom\LaravelPaytabs\Core;

/**
 * @abstract class: Enum for static values of PayTabs requests
 */
abstract class PaytabsEnum
{
    const TRAN_TYPE_AUTH = 'auth';
    const TRAN_TYPE_CAPTURE = 'capture';
    const TRAN_TYPE_SALE = 'sale';

    const TRAN_TYPE_VOID = 'void';
    const TRAN_TYPE_REFUND = 'refund';

    //

    const TRAN_CLASS_ECOM = 'ecom';
    const TRAN_CLASS_MOTO = 'moto';
    const TRAN_CLASS_RECURRING = 'recurring';

    //

    static function TranIsAuth($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_AUTH) == 0;
    }

    static function TranIsSale($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_SALE) == 0;
    }
}