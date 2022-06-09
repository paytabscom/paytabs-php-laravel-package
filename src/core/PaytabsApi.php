<?php

namespace Paytabscom\LaravelPaytabs\Core;

use stdClass;

/**
 * API class which contacts PayTabs server's API
 */
class PaytabsApi
{
    const PAYMENT_TYPES = [
        '0' => ['name' => 'all', 'title' => 'PayTabs - All', 'currencies' => null],
        '1' => ['name' => 'stcpay', 'title' => 'PayTabs - StcPay', 'currencies' => ['SAR']],
        '2' => ['name' => 'stcpayqr', 'title' => 'PayTabs - StcPay(QR)', 'currencies' => ['SAR']],
        '3' => ['name' => 'applepay', 'title' => 'PayTabs - ApplePay', 'currencies' => ['AED', 'SAR']],
        '4' => ['name' => 'omannet', 'title' => 'PayTabs - OmanNet', 'currencies' => ['OMR']],
        '5' => ['name' => 'mada', 'title' => 'PayTabs - Mada', 'currencies' => ['SAR']],
        '6' => ['name' => 'creditcard', 'title' => 'PayTabs - CreditCard', 'currencies' => null],
        '7' => ['name' => 'sadad', 'title' => 'PayTabs - Sadad', 'currencies' => ['SAR']],
        '8' => ['name' => 'atfawry', 'title' => 'PayTabs - @Fawry', 'currencies' => ['EGP']],
        '9' => ['name' => 'knet', 'title' => 'PayTabs - KnPay', 'currencies' => ['KWD']],
        '10' => ['name' => 'amex', 'title' => 'PayTabs - Amex', 'currencies' => ['AED', 'SAR']],
        '11' => ['name' => 'valu', 'title' => 'PayTabs - valU', 'currencies' => ['EGP']],
    ];
    const BASE_URLS = [
        'ARE' => [
            'title' => 'United Arab Emirates',
            'endpoint' => 'https://secure.paytabs.com/'
        ],
        'SAU' => [
            'title' => 'Saudi Arabia',
            'endpoint' => 'https://secure.paytabs.sa/'
        ],
        'OMN' => [
            'title' => 'Oman',
            'endpoint' => 'https://secure-oman.paytabs.com/'
        ],
        'JOR' => [
            'title' => 'Jordan',
            'endpoint' => 'https://secure-jordan.paytabs.com/'
        ],
        'EGY' => [
            'title' => 'Egypt',
            'endpoint' => 'https://secure-egypt.paytabs.com/'
        ],
        'GLOBAL' => [
            'title' => 'Global',
            'endpoint' => 'https://secure-global.paytabs.com/'
        ],
        // 'DEMO' => [
        //     'title' => 'Demo',
        //     'endpoint' => 'https://secure-demo.paytabs.com/'
        // ],
    ];

    // const BASE_URL = 'https://secure.paytabs.com/';

    const URL_REQUEST = 'payment/request';
    const URL_QUERY = 'payment/query';

    const URL_TOKEN_QUERY = 'payment/token';
    const URL_TOKEN_DELETE = 'payment/token/delete';

    //

    private $base_url;
    private $profile_id;
    private $server_key;

    //

    private static $instance = null;

    //

    public static function getEndpoints()
    {
        $endpoints = [];
        foreach (PaytabsApi::BASE_URLS as $key => $value) {
            $endpoints[$key] = $value['title'];
        }
        return $endpoints;
    }

    public static function getInstance($region, $merchant_id, $key)
    {
        if (self::$instance == null) {
            self::$instance = new PaytabsApi($region, $merchant_id, $key);
        }

        // self::$instance->setAuth($merchant_email, $secret_key);

        return self::$instance;
    }

    private function __construct($region, $profile_id, $server_key)
    {
        $this->base_url = self::BASE_URLS[$region]['endpoint'];
        $this->setAuth($profile_id, $server_key);
    }

    private function setAuth($profile_id, $server_key)
    {
        $this->profile_id = $profile_id;
        $this->server_key = $server_key;
    }


    /** start: API calls */

    function create_pay_page($values)
    {
        // $serverIP = getHostByName(getHostName());
        // $values['ip_merchant'] = PaytabsHelper::getNonEmpty($serverIP, $_SERVER['SERVER_ADDR'], 'NA');

        $isTokenize = array_key_exists('token', $values);

        $response = $this->sendRequest(self::URL_REQUEST, $values);

        $res = json_decode($response);
        $paypage = $isTokenize ? $this->enhanceTokenization($res) : $this->enhance($res);

        return $paypage;
    }

    function verify_payment($tran_reference)
    {
        $values['tran_ref'] = $tran_reference;
        $verify = json_decode($this->sendRequest(self::URL_QUERY, $values));

        $verify = $this->enhanceVerify($verify);

        return $verify;
    }

    function request_followup($values)
    {
        $res = json_decode($this->sendRequest(self::URL_REQUEST, $values));
        $refund = $this->enhanceRefund($res);

        return $refund;
    }

    function token_query($token)
    {
        $values = ['token' => $token];
        $res = json_decode($this->sendRequest(self::URL_TOKEN_QUERY, $values));

        return $res;
    }

    function token_delete($token)
    {
        $values = ['token' => $token];
        $res = json_decode($this->sendRequest(self::URL_TOKEN_DELETE, $values));

        return $res;
    }

    //

    function is_valid_redirect($post_values)
    {
        $serverKey = $this->server_key;

        // Request body include a signature post Form URL encoded field
        // 'signature' (hexadecimal encoding for hmac of sorted post form fields)
        $requestSignature = $post_values["signature"];
        unset($post_values["signature"]);
        $fields = array_filter($post_values);

        // Sort form fields
        ksort($fields);

        // Generate URL-encoded query string of Post fields except signature field.
        $query = http_build_query($fields);

        return $this->is_genuine($query, $requestSignature, $serverKey);
    }


    function is_valid_ipn($data, $signature, $serverkey = false)
    {
        $server_key = $serverKey ?? $this->server_key;

        return $this->is_genuine($data, $signature, $server_key);
    }


    private function is_genuine($data, $requestSignature, $serverKey)
    {
        $signature = hash_hmac('sha256', $data, $serverKey);

        if (hash_equals($signature, $requestSignature) === TRUE) {
            // VALID Redirect
            return true;
        } else {
            // INVALID Redirect
            return false;
        }
    }

    /** end: API calls */


    /** start: Local calls */

    /**
     *
     */
    private function enhance($paypage)
    {
        $_paypage = $paypage;

        if (!$paypage) {
            $_paypage = new stdClass();
            $_paypage->success = false;
            $_paypage->message = 'Create paytabs payment failed';
        } else {
            $_paypage->success = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);

            $_paypage->payment_url = @$paypage->redirect_url;
        }

        return $_paypage;
    }

    private function enhanceVerify($verify)
    {
        $_verify = $verify;

        if (!$verify) {
            $_verify = new stdClass();
            $_verify->success = false;
            $_verify->message = 'Verifying paytabs payment failed';
        } else if (isset($verify->code, $verify->message)) {
            $_verify->success = false;
        } else {
            if (isset($verify->payment_result)) {
                $_verify->success = $verify->payment_result->response_status == "A";
            } else {
                $_verify->success = false;
            }
            $_verify->message = $verify->payment_result->response_message;
        }

        $_verify->reference_no = @$verify->cart_id;
        $_verify->transaction_id = @$verify->tran_ref;

        return $_verify;
    }

    private function enhanceRefund($refund)
    {
        $_refund = $refund;

        if (!$refund) {
            $_refund = new stdClass();
            $_refund->success = false;
            $_refund->message = 'Verifying paytabs Refund failed';
        } else {
            if (isset($refund->payment_result)) {
                $_refund->success = $refund->payment_result->response_status == "A";
                $_refund->message = $refund->payment_result->response_message;
            } else {
                $_refund->success = false;
            }
            $_refund->pending_success = false;
        }

        return $_refund;
    }

    private function enhanceTokenization($paypage)
    {
        $_paypage = $paypage;

        if (!$paypage) {
            $_paypage = new stdClass();
            $_paypage->success = false;
            $_paypage->message = 'Create paytabs tokenization payment failed';
        } else {
            $is_redirect = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);
            $is_completed = isset($paypage->payment_result);

            if ($is_redirect) {
                $_paypage->success = true;
                $_paypage->payment_url = $paypage->redirect_url;
            } else if ($is_completed) {
                $_paypage = $this->enhanceVerify($paypage);
            } else {
                $_paypage = $this->enhance($paypage);
            }

            $_paypage->is_redirect = $is_redirect;
            $_paypage->is_completed = $is_completed;
        }

        return $_paypage;
    }

    /** end: Local calls */

    private function sendRequest($request_url, $values)
    {
        $auth_key = $this->server_key;
        $gateway_url = $this->base_url . $request_url;

        $headers = [
            'Content-Type: application/json',
            "Authorization: {$auth_key}"
        ];

        $values['profile_id'] = (int)$this->profile_id;
        $post_params = json_encode($values);

        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_URL, $gateway_url);
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_HEADER, false);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        @curl_setopt($ch, CURLOPT_VERBOSE, true);
        // @curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = @curl_exec($ch);

        $error_num = curl_errno($ch);
        if ($error_num) {
            $error_msg = curl_error($ch);
            PaytabsHelper::log("Paytabs Admin: Response [($error_num) $error_msg], [$result]", 3);

            $result = json_encode([
                'message' => 'Sorry, unable to process your transaction, Contact the site Administrator'
            ]);
        }

        @curl_close($ch);

        return $result;
    }
}
