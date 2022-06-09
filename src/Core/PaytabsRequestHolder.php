<?php

namespace Paytabscom\LaravelPaytabs\Core;

/**
 * Holder class, Inherit class PaytabsHolder
 * Holds & Generates the parameters array that pass to PayTabs' API
 */
class PaytabsRequestHolder extends PaytabsHolder
{
    /**
     * payment_type
     */
    private $payment_code;

    /**
     * name
     * email
     * phone
     * street1
     * city
     * state
     * country
     * zip
     * ip
     */
    private $customer_details;

    /**
     * name
     * email
     * phone
     * street1
     * city
     * state
     * country
     * zip
     * ip
     */
    private $shipping_details;

    /**
     * hide_shipping
     */
    private $hide_shipping;

    /**
     * pan
     * expiry_month
     * expiry_year
     * cvv
     */
    private $card_details;

    /**
     * return
     * callback
     */
    private $urls;

    /**
     * paypage_lang
     */
    private $lang;

    /**
     * framed
     */
    private $framed;

    /**
     * tokenise
     * show_save_card
     */
    private $tokenise;


    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $this->pt_merges(
            $all,
            $this->payment_code,
            $this->urls,
            $this->customer_details,
            $this->shipping_details,
            $this->hide_shipping,
            $this->lang,
            $this->framed,
            $this->tokenise
        );

        return $all;
    }


    private function setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        // PaytabsHelper::pt_fillIfEmpty($name);
        // $this->_fill($address, 'NA');

        // PaytabsHelper::pt_fillIfEmpty($city);

        // $this->_fill($state, $city, 'NA');

        if ($zip) {
            $zip = PaytabsHelper::convertAr2En($zip);
        }

        if (!$ip) {
            PaytabsHelper::pt_fillIP($ip);
        }

        //

        $info = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'street1' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'zip' => $zip,
            'ip' => $ip
        ];

        return $info;
    }

    //

    public function set01PaymentCode($code)
    {
        $this->payment_code = ['payment_methods' => [$code]];

        return $this;
    }


    public function set04CustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        $infos = $this->setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);

        //

        $this->customer_details = [
            'customer_details' => $infos
        ];

        return $this;
    }

    public function set05ShippingDetails($same_as_billing, $name = null, $email = null, $phone = null, $address = null, $city = null, $state = null, $country = null, $zip = null, $ip = null)
    {
        $infos = $same_as_billing
            ? $this->customer_details['customer_details']
            : $this->setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);

        //

        $this->shipping_details = [
            'shipping_details' => $infos
        ];

        return $this;
    }

    public function set06HideShipping($on = false)
    {
        $this->hide_shipping = [
            'hide_shipping' => $on,
        ];

        return $this;
    }

    public function set07URLs($return_url, $callback_url)
    {
        $this->urls = [
            'return' => $return_url,
            'callback' => $callback_url,
        ];

        return $this;
    }

    public function set08Lang($lang_code)
    {
        $this->lang = [
            'paypage_lang' => $lang_code
        ];

        return $this;
    }

    /**
     * @param string $redirect_target "parent" or "top" or "iframe"
     */
    public function set09Framed($on = false, $redirect_target = 'iframe')
    {
        $this->framed = [
            'framed' => $on,
            'framed_return_parent' => $redirect_target == 'parent',
            'framed_return_top' => $redirect_target == 'top'
        ];

        return $this;
    }

    /**
     * @param int $token_format integer between 2 and 6, Set the Token format
     * @param bool $optional Display the save card option on the payment page
     */
    public function set10Tokenise($on = false, $token_format = 2, $optional = false)
    {
        if ($on) {
            $this->tokenise = [
                'tokenise' => $token_format,
                'show_save_card' => $optional
            ];
        }

        return $this;
    }
}