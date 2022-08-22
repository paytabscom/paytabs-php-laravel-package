<?php


namespace Paytabscom\Laravel_paytabs;


class paytabs_core
{
}


/**
 * PayTabs v2 PHP SDK
 * Version: 2.0.8
 */
abstract class PaytabsHelper
{
    static function paymentType($key)
    {
        return PaytabsApi::PAYMENT_TYPES[$key]['name'];
    }

    static function paymentAllowed($code, $currencyCode)
    {
        $row = null;
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                $row = $value;
                break;
            }
        }
        if (!$row) {
            return false;
        }
        $list = $row['currencies'];
        if ($list == null) {
            return true;
        }

        $currencyCode = strtoupper($currencyCode);

        return in_array($currencyCode, $list);
    }

    static function isPayTabsPayment($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return the first non-empty var from the vars list
     * @return null if all params are empty
     */
    public static function getNonEmpty(...$vars)
    {
        foreach ($vars as $var) {
            if (!empty($var)) return $var;
        }
        return null;
    }

    /**
     * convert non-english digits to English
     * used for fileds that accepts only English digits like: "postal_code"
     */
    public static function convertAr2En($string)
    {
        $nonEnglish = [
            // Arabic
            [
                '٠',
                '١',
                '٢',
                '٣',
                '٤',
                '٥',
                '٦',
                '٧',
                '٨',
                '٩'
            ],
            // Persian
            [
                '۰',
                '۱',
                '۲',
                '۳',
                '۴',
                '۵',
                '۶',
                '۷',
                '۸',
                '۹'
            ]
        ];

        $num = range(0, 9);

        $englishNumbersOnly = $string;
        foreach ($nonEnglish as $oldNum) {
            $englishNumbersOnly = str_replace($oldNum, $num, $englishNumbersOnly);
        }

        return $englishNumbersOnly;
    }

    /**
     * check Strings that require to be a valid Word, not [. (dot) or digits ...]
     * if the parameter is not a valid "Word", convert it to "NA"
     */
    public static function pt_fillIfEmpty(&$string)
    {
        if (empty(preg_replace('/[\W]/', '', $string))) {
            $string .= 'NA';
        }
    }

    static function pt_fillIP(&$string)
    {
        $string = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * <b>paytabs_error_log<b> should be defined,
     * Main functionality: use the platform logger to log the error messages
     * If not found: create a new log file and log the messages
     */
    public static function log($msg, $severity = 1)
    {
        try {
            paytabs_error_log($msg, $severity);
        } catch (\Throwable $th) {
            try {
                $_prefix = date('c') . ' PayTabs: ';
                $_msg = ($_prefix . $msg . PHP_EOL);
                file_put_contents('debug_paytabs.log', $_msg, FILE_APPEND);
            } catch (\Throwable $th) {
                // var_export($th);
            }
        }
    }

    static function getTokenInfo($return_values)
    {
        $fields = [
            'pt_token',
            'pt_customer_email',
            'pt_customer_password'
        ];

        $tokenInfo = [];

        foreach ($fields as $field) {
            if (!isset($return_values[$field])) return false;
            $tokenInfo[$field] = $return_values[$field];
        }

        return $tokenInfo;
    }
}


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


    /**
     * custom values passed from the merchant
     */
    private $user_defined;


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
            $this->tokenise,
            $this->user_defined
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

    public function set100userDefined($user_defined = [])
    {
        
        $this->user_defined = [
            'user_defined' => $user_defined
        ];
        return $this;
    }
}


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

