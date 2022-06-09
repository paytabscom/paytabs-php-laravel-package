<?php

namespace Paytabscom\LaravelPaytabs\Core;

/**
 * Holder class, Inherit class PaytabsHolder
 * Holds & Generates the parameters array for the Tokenised payments
 */
class PaytabsTokenHolder extends PaytabsHolder
{
    /**
     * token
     * tran_ref
     */
    private $token_info;


    public function set20Token($token, $tran_ref)
    {
        $this->token_info = [
            'token' => $token,
            'tran_ref' => $tran_ref
        ];

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->token_info);

        return $all;
    }
}