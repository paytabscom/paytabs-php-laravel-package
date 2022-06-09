<?php

namespace Paytabscom\LaravelPaytabs\Core;

/**
 * Holder class: Holds & Generates the parameters array that pass to PayTabs' API
 */
class PaytabsHolder
{
    /**
     * tran_type
     * tran_class
     */
    private $transaction;

    /**
     * cart_id
     * cart_currency
     * cart_amount
     * cart_descriptions
     */
    private $cart;

    /**
     * cart_name
     * cart_version
     * plugin_version
     */
    private $plugin_info;


    //


    /**
     * @return array
     */
    public function pt_build()
    {
        $all = array_merge(
            $this->transaction,
            $this->cart,
            $this->plugin_info
        );

        return $all;
    }

    protected function pt_merges(&$all, ...$arrays)
    {
        foreach ($arrays as $array) {
            if ($array) {
                $all = array_merge($all, $array);
            }
        }
    }

    //

    public function set02Transaction($tran_type, $tran_class = PaytabsEnum::TRAN_CLASS_ECOM)
    {
        $this->transaction = [
            'tran_type' => $tran_type,
            'tran_class' => $tran_class,
        ];

        return $this;
    }

    public function set03Cart($cart_id, $currency, $amount, $cart_description)
    {
        $this->cart = [
            'cart_id' => "$cart_id",
            'cart_currency' => "$currency",
            'cart_amount' => (float)$amount,
            'cart_description' => $cart_description,
        ];

        return $this;
    }

    public function set99PluginInfo($platform_name, $platform_version, $plugin_version)
    {
        $this->plugin_info = [
            'plugin_info' => [
                'cart_name' => $platform_name,
                'cart_version' => "{$platform_version}",
                'plugin_version' => "{$plugin_version}",
            ]
        ];

        return $this;
    }
}