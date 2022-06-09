<?php

namespace Paytabscom\LaravelPaytabs\Core;

/**
 * Holder class, Inherit class PaytabsHolder
 * Holder & Generates the parameters array for the Followup requests
 * Followup requests:
 * - Capture (follows Auth)
 * - Void    (follows Auth)
 * - Refund  (follows Capture or Sale)
 */
class PaytabsFollowupHolder extends PaytabsHolder
{
    /**
     * transaction_id
     */
    private $transaction_id;

    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->transaction_id);

        return $all;
    }

    //

    public function set30TransactionInfo($transaction_id)
    {
        $this->transaction_id = [
            'tran_ref' => $transaction_id,
        ];

        return $this;
    }
}