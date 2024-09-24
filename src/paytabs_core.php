<?php


namespace Paytabscom\Laravel_paytabs;


class paytabs_core
{
}

/**
 * PayTabs v2 PHP SDK
 * Version: 2.24.0
 * PHP >= 7.0.0
 */

define('PAYTABS_SDK_VERSION', '2.24.0');

define('PAYTABS_DEBUG_FILE_NAME', 'debug_paytabs.log');
define('PAYTABS_DEBUG_SEVERITY', ['Info', 'Warning', 'Error']);
define('PAYTABS_PREFIX', 'PayTabs');


abstract class PaytabsHelper
{
    static function paymentType($key)
    {
        return PaytabsApi::PAYMENT_TYPES[$key]['name'];
    }

    static function getPaymentMethodDetails($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $k => $v) {
            if ($v['name'] == $code) {
                return $v;
            }
        }
        return false;
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

    static function isCardPayment($code, $is_international = false)
    {
        $group = $is_international ? PaytabsApi::GROUP_CARDS_INTERNATIONAL : PaytabsApi::GROUP_CARDS;

        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array($group, $value['groups']);
            }
        }
        return false;
    }

    static function getCardPayments($international_only = false, $currency = null)
    {
        $methods = [];

        $group = $international_only ? PaytabsApi::GROUP_CARDS_INTERNATIONAL : PaytabsApi::GROUP_CARDS;

        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if (in_array($group, $value['groups'])) {
                if ($currency) {
                    if ($value['currencies'] == null || in_array($currency, $value['currencies'])) {
                        $methods[] = $value['name'];
                    }
                } else {
                    $methods[] = $value['name'];
                }
            }
        }
        return $methods;
    }

    /**
     * @return true if the payment method can use the Card methods features
     */
    static function canUseCardFeatures($code)
    {
        return ($code == 'all') || static::isCardPayment($code);
    }

    static function supportTokenization($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(PaytabsApi::GROUP_TOKENIZE, $value['groups']);
            }
        }
        return false;
    }

    static function supportAuthCapture($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(PaytabsApi::GROUP_AUTH_CAPTURE, $value['groups']);
            }
        }
        return false;
    }

    static function supportIframe($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(PaytabsApi::GROUP_IFRAME, $value['groups']);
            }
        }
        return false;
    }

    static function supportRefund($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(PaytabsApi::GROUP_REFUND, $value['groups']);
            }
        }
        return false;
    }

    static function supportPending($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(PaytabsApi::GROUP_PENDING, $value['groups']);
            }
        }
        return false;
    }

    //

    static function read_ipn_response()
    {
        $response = file_get_contents('php://input');
        $data = json_decode($response);

        return $data;
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
     * used for fields that accepts only English digits like: "postal_code"
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
     * @param $severity: [1: info, 2: warning, 3: error]
     */
    public static function log($msg, $severity = 1)
    {
        try {
            paytabs_error_log($msg, $severity);
        } catch (\Throwable $th) {
            try {
                $severity_str = PAYTABS_DEBUG_SEVERITY[--$severity];
                $_prefix = date('c') . " " . PAYTABS_PREFIX . ".{$severity_str} (FB): ";
                $_msg = ($_prefix . $msg . PHP_EOL);

                $_file = defined('PAYTABS_DEBUG_FILE') ? PAYTABS_DEBUG_FILE : PAYTABS_DEBUG_FILE_NAME;
                file_put_contents($_file, $_msg, FILE_APPEND);
            } catch (\Throwable $th) {
                // var_export($th);
            }
        }
    }

    static function isValidDiscountPattern($pattern)
    {
        return preg_match(PaytabsEnum::DISCOUNT_PATTERN_REGEX, $pattern);
    }

    /**
     * Validate the patterns for discount option
     * @param $patterns_str string, comma separated
     */
    static function isValidDiscountPatterns($patterns_str)
    {
        $patterns = explode(',', $patterns_str);

        if (empty($patterns)) return false;

        foreach ($patterns as $prefix) {
            if (!static::isValidDiscountPattern($prefix)) {
                return false;
            }
        }

        return true;
    }

    /** Checks if the PT payload has applied a valid discount.
     * @return bool
     */
    static function hasDiscountApplied($patterns, $amounts, $types, $pt_response)
    {
        $applied_discounts = self::getAppliedDiscounts($patterns, $pt_response);
        if (empty($applied_discounts)) return false;

        $cart_amount = (float)$pt_response->cart_amount;
        $tran_total = (float)$pt_response->tran_total;
        if ($cart_amount == $tran_total) return false;

        $_idxs = implode(',', $applied_discounts);
        PaytabsHelper::log("Discount flag detected, Amount: {$cart_amount} >> {$tran_total}, Indexes [{$_idxs}]", 1);

        foreach ($applied_discounts as $i) {
            $discount_amount = $amounts[$i];
            $discount_type = $types[$i];

            $valid_discount = self::isValidDiscount($discount_type, $discount_amount, $cart_amount, $tran_total);
            if ($valid_discount) {
                return $i;
            }
        }

        return false;
    }

    /**
     * If the response payload contains the Discount flag set
     * And if the used card in the trx matches any of the card patterns in the system
     * @return array of indexes of the used card patterns
     */
    static function getAppliedDiscounts($patterns, $pt_response, $udf_key = 'udf3')
    {
        $user_defined = @$pt_response->user_defined;
        if (!$user_defined) return false;

        $udf3 = @$user_defined->$udf_key;
        if (!$udf3) return false;

        if ($udf3 != PaytabsEnum::DISCOUNT_FLAG) return false;

        $card_used = @$pt_response->payment_info->payment_description;
        if (!$card_used) return false;

        $card_used = substr(str_replace(' ', '', $card_used), 0, 6);

        $count = count($patterns);
        if ($count < 1) return false;

        $indexes = [];

        $card_used_length = strlen($card_used);
        for ($i = 0; $i < $count; $i++) {
            $pattern_str = $patterns[$i];
            $patterns_arr = explode(',', $pattern_str);
            foreach ($patterns_arr as $pattern) {
                $shared_length = min(strlen($pattern), $card_used_length);

                if (substr($pattern, 0, $shared_length) == substr($card_used, 0, $shared_length)) {
                    PaytabsHelper::log("Discount matched: Card ($card_used), {$pattern_str} => {$pattern}", 1);

                    $indexes[] = $i;
                }
            }
        }

        if (count($indexes) > 0) {
            return $indexes;
        }

        return false;
    }

    /**
     * Validate the discount amount value.
     * @return bool
     */
    static function isValidDiscount($discount_type, $discount_amount, $amount_original, $amount_discounted)
    {
        $amount_original = (float)$amount_original;
        $amount_discounted = (float)$amount_discounted;
        // if ($amount_original == $amount_discounted) return false;

        $computed_discount = 0;
        $computed_amount = 0;

        if ($discount_type == PaytabsEnum::DISCOUNT_FIXED) {
            $computed_discount = $discount_amount;
        } elseif ($discount_type == PaytabsEnum::DISCOUNT_PERCENTAGE) {
            $computed_discount = $amount_original * $discount_amount / 100;
        }

        $computed_amount = $amount_original - $computed_discount;

        //

        $tolerancePercentage = 0.1;

        // Calculate the percentage difference
        $percentageDifference = abs((($amount_discounted - $computed_amount) / $amount_discounted) * 100);

        if ($percentageDifference <= $tolerancePercentage) {
            // PaytabsHelper::log("Discount values: {$amount_original}, {$computed_amount}", 1);
            return true;
        } else {
            PaytabsHelper::log("Exceeded the tolerance, Discount values: {$amount_original}, {$computed_amount}", 2);
        }

        return false;
    }
}


/**
 * @abstract class: Enum for static values of PayTabs requests
 */
abstract class PaytabsEnum
{
    const TRAN_TYPE_AUTH     = 'auth';
    const TRAN_TYPE_CAPTURE  = 'capture';
    const TRAN_TYPE_SALE     = 'sale';
    const TRAN_TYPE_REGISTER = 'register';
    // Auth Extension is used to refresh the hold on the funds
    // Followup an Auth transaction
    const TRAN_TYPE_AUTH_EXT = 'authext';

    const TRAN_TYPE_PAYMENT_REQUEST = 'payment request';

    const TRAN_TYPE_VOID    = 'void';
    const TRAN_TYPE_RELEASE = 'release';
    const TRAN_TYPE_REFUND  = 'refund';

    //

    const TRAN_CLASS_ECOM = 'ecom';
    const TRAN_CLASS_MOTO = 'moto';
    const TRAN_CLASS_RECURRING = 'recurring';

    //

    const TRAN_STATUS_Authorised = 'A';
    const TRAN_STATUS_OnHold     = 'H';
    const TRAN_STATUS_Pending    = 'P';
    const TRAN_STATUS_Voided     = 'V';
    const TRAN_STATUS_Error      = 'E';
    const TRAN_STATUS_Declined   = 'D';
    const TRAN_STATUS_Expired    = 'X';

    //

    const TOKEN_TYPE_REGISTERED = 'registered';
    const TOKEN_TYPE_UNSCHEDULED = 'unscheduled';
    const TOKEN_TYPE_RECURRING_FIXED = 'recurring_fixed';
    const TOKEN_TYPE_RECURRING_VARIABLE = 'recurring_variable';

    //

    const PP_ERR_DUPLICATE = 4;

    //

    const DISCOUNT_FLAG = 'flag.card_discount';
    const DISCOUNT_PERCENTAGE = "percentage";
    const DISCOUNT_FIXED = "fixed";

    const DISCOUNT_PATTERN_REGEX = '/^[0-9]{4,10}$/';

    //


    static function TranIsAuth($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_AUTH) == 0;
    }

    static function TranIsAuthExt($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_AUTH_EXT) == 0;
    }

    static function TranIsSale($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_SALE) == 0;
    }

    static function TranIsRegister($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_REGISTER) == 0;
    }

    static function TranIsPaymentRequest($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_PAYMENT_REQUEST) == 0;
    }

    static function TranIsCapture($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_CAPTURE) == 0;
    }

    static function TranIsVoid($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_VOID) == 0;
    }

    static function TranIsRelease($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_RELEASE) == 0;
    }

    static function TranIsRefund($tran_type)
    {
        return strcasecmp($tran_type, PaytabsEnum::TRAN_TYPE_REFUND) == 0;
    }

    static function TransAreSame($tran_type1, $tran_type2)
    {
        return strcasecmp($tran_type1, $tran_type2) == 0;
    }


    static function TranIsPaymentComplete($ipn_data)
    {
        if ($ipn_data) {
            $original_trx = @$ipn_data->previous_tran_ref;
            $tran_type = $ipn_data->tran_type;

            // Sale && previous_tran_ref
            if (PaytabsEnum::TranIsSale($tran_type) && isset($original_trx)) {
                return true;
            }

            // Or Expired
            $tran_status = @$ipn_data->payment_result->response_status;
            if ($tran_status === 'X') {
                return true;
            }
        }

        return false;
    }

    //

    static function TranStatusIsSuccess($tran_response_status)
    {
        return $tran_response_status == PaytabsEnum::TRAN_STATUS_Authorised;
    }

    static function TranStatusIsOnHold($tran_response_status)
    {
        return $tran_response_status == PaytabsEnum::TRAN_STATUS_OnHold;
    }

    static function TranStatusIsPending($tran_response_status)
    {
        return $tran_response_status == PaytabsEnum::TRAN_STATUS_Pending;
    }

    static function TranStatusIsExpired($tran_response_status)
    {
        return $tran_response_status == PaytabsEnum::TRAN_STATUS_Expired;
    }

    //

    static function PPIsDuplicate($paypage)
    {
        $err_code = @$paypage->code;
        return $err_code == PaytabsEnum::PP_ERR_DUPLICATE;
    }
}


/**
 * Holder class: Holds & Generates the parameters array that pass to PayTabs' API
 * Members:
 * - Transaction Info (Type & Class)
 * - Cart Info (id, desc, amount, currency)
 * - URLs (return & callback)
 * - Plugin Info (platform name, platform version, plugin version)
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
     * return
     * callback
     */
    private $urls;

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
        $all = [];
        $this->pt_merges(
            $all,
            $this->transaction,
            $this->cart,
            $this->urls,
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
            'tran_type'  => $tran_type,
            'tran_class' => $tran_class,
        ];

        return $this;
    }

    public function set03Cart($cart_id, $currency, $amount, $cart_description)
    {
        $this->cart = [
            'cart_id'          => "$cart_id",
            'cart_currency'    => "$currency",
            'cart_amount'      => (float) $amount,
            'cart_description' => $cart_description,
        ];

        return $this;
    }

    public function set07URLs($return_url, $callback_url)
    {
        $this->urls = [
            'return'   => $return_url,
            'callback' => $callback_url,
        ];

        return $this;
    }

    public function set99PluginInfo($platform_name, $platform_version, $plugin_version = null)
    {
        if (!$plugin_version) {
            $plugin_version = PAYTABS_SDK_VERSION;
        }

        $this->plugin_info = [
            'plugin_info' => [
                'cart_name'    => $platform_name,
                'cart_version' => "{$platform_version}",
                'plugin_version' => "{$plugin_version}",
            ]
        ];

        return $this;
    }
}


/**
 * Holder class: Holds & Generates the parameters array.
 * Holds & Generates the parameters array for the payments
 * Members:
 * - airline_data
 *  -- pnr_code
 */
abstract class PaytabsExtraDataHolder extends PaytabsHolder
{
    private $airline_data;

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $this->pt_merges(
            $all,
            $this->airline_data,
        );

        return $all;
    }

    public function set60AirlineData($pnr_code)
    {
        if (!is_null($pnr_code)) {
            $this->airline_data = [
                'airline_data' => [
                    'pnr_code' => $pnr_code,
                ],
            ];
        }

        return $this;
    }
}


/**
 * Holder class, Inherit class PaytabsHolder
 * Holds & Generates the parameters array that pass to PayTabs' API
 * Members:
 * - Payment method (payment_code)
 * - Customer Details
 * - Shipping Details
 * - Language (paypage_lang)
 * - Tokenise
 * - User defined
 */
abstract class PaytabsBasicHolder extends PaytabsExtraDataHolder
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
     * paypage_lang
     */
    private $lang;

    /**
     * tokenise
     * show_save_card
     */
    private $tokenise;

    /**
     * tokenise
     * token_type
     * counter
     * total_count
     * show_save_card
     */
    private $token_info;

    /**
     * udf[1-9]
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
            $this->customer_details,
            $this->shipping_details,
            $this->lang,
            $this->tokenise,
            $this->token_info,
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

        $info =  [
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'street1' => $address,
            'city'    => $city,
            'state'   => $state,
            'country' => $country,
            'zip'     => $zip,
            'ip'      => $ip
        ];

        return $info;
    }

    //

    public function set01PaymentCode($code, $allow_associated_methods = true, $currency = null)
    {
        $codes = [$code];

        if (PaytabsHelper::isCardPayment($code)) {
            if ($allow_associated_methods) {
                if (PaytabsHelper::isCardPayment($code, true)) {
                    $other_cards = PaytabsHelper::getCardPayments(false, $currency);
                } else {
                    $other_cards = PaytabsHelper::getCardPayments(true, $currency);
                }
                $codes = array_unique(array_merge($other_cards, $codes));
            }
        }

        // 'creditcard' => ['creditcard', 'mada', 'omannet', 'meeza']

        foreach ($codes as &$code) {
            if (substr($code, 0, 3) === "pt_") {
                $code = substr($code, 3);
            }
        }

        $this->payment_code = ['payment_methods' => $codes];

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


    public function set08Lang($lang_code)
    {
        $this->lang = [
            'paypage_lang' => $lang_code
        ];

        return $this;
    }


    /**
     * @deprecated
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


    /**
     * @param int $token_format integer between 2 and 6, Set the Token format
     * @param string $token_type
     * @param int $counter
     * @param int $total_count
     * @param bool $optional Display the save card option on the payment page
     */
    public function set11TokeniseInfo($on, $token_format = 2, $token_type = PaytabsEnum::TOKEN_TYPE_UNSCHEDULED, $counter = null, $total_count = null, $optional = false)
    {
        if ($on) {
            $_info = [
                'tokenise' => $token_format,
                'token_type' => $token_type,
            ];
            if (!is_null($counter)) {
                $_info['counter'] = $counter;
            }
            if (!is_null($total_count)) {
                $_info['total_count'] = $total_count;
            }

            $this->token_info = [
                'token_info' => $_info,
                'show_save_card' => $optional
            ];
        }

        return $this;
    }


    public function set50UserDefined($udf1, $udf2 = null, $udf3 = null, $udf4 = null, $udf5 = null, $udf6 = null, $udf7 = null, $udf8 = null, $udf9 = null)
    {
        $user_defined = [];

        for ($i = 1; $i <= 9; $i++) {
            $param = "udf$i";
            if ($$param != null) {
                $user_defined[$param] = $$param;
            }
        }

        $this->user_defined = [
            'user_defined' => $user_defined
        ];

        return $this;
    }
}


/**
 * Holder class, Inherit class PaytabsBasicHolder
 * Holds & Generates the parameters array that pass to PayTabs' API
 * Members:
 * - Hide shipping
 * - Framed
 */
class PaytabsRequestHolder extends PaytabsBasicHolder
{
    /**
     * hide_shipping
     */
    private $hide_shipping;

    /**
     * framed
     */
    private $framed;

    /**
     * config_id
     */
    private $config_id;

    /**
     * alt_currency
     */
    private $alt_currency;

    /**
     * card_discounts
     */
    private $card_discounts;

    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $this->pt_merges(
            $all,
            $this->hide_shipping,
            $this->framed,
            $this->config_id,
            $this->alt_currency,
            $this->card_discounts
        );

        return $all;
    }

    public function set06HideShipping(bool $on = false)
    {
        $this->hide_shipping = [
            'hide_shipping' => $on,
        ];

        return $this;
    }

    /**
     * @param string $redirect_target "parent" or "top" or "iframe"
     */
    public function set09Framed(bool $on = false, $redirect_target = 'iframe')
    {
        $this->framed = [
            'framed' => $on,
            'framed_return_parent' => $redirect_target == 'parent',
            'framed_return_top' => $redirect_target == 'top'
        ];

        return $this;
    }

    public function set11ThemeConfigId($config_id)
    {
        $config_id = (int) trim($config_id ?? "");

        if (is_int($config_id) && $config_id > 0) {
            $this->config_id = [
                'config_id' => $config_id
            ];
        }

        return $this;
    }

    public function set12AltCurrency($alt_currency)
    {
        $alt_currency = trim($alt_currency ?? "");

        if (!empty($alt_currency)) {
            $this->alt_currency = [
                'alt_currency' => $alt_currency
            ];
        }
        return $this;
    }

    public function set13CardDiscounts($discount_patterns, $discount_amounts, $discount_types, $flag_set = false)
    {
        if (empty($discount_patterns)) {
            PaytabsHelper::log('Paytabs admin: Discount arrays must be not empty', 3);
            return $this;
        }

        $count = count($discount_patterns);

        if ($count != count($discount_amounts) || $count != count($discount_types)) {
            PaytabsHelper::log('Paytabs admin: Discount arrays must have the same length', 3);
            return $this;
        }

        $cards = [];

        for ($i = 0; $i < $count; $i++) {
            $pattern = $discount_patterns[$i];
            $amount = $discount_amounts[$i];
            $type = $discount_types[$i];

            if (!PaytabsHelper::isValidDiscountPattern($pattern)) {
                PaytabsHelper::log('Paytabs admin: Discount pattern not valid', 2);
                // uncomment if you want to stop the request, otherwise send the request
                // return $this;
            }

            if ($type == PaytabsEnum::DISCOUNT_PERCENTAGE) {
                $type_key = 'discount_percent';
                $title = "$discount_amounts[$i]% discount applied on cards starting with $discount_patterns[$i]";
            } elseif ($type == PaytabsEnum::DISCOUNT_FIXED) {
                $type_key = 'discount_amount';
                $title = "$discount_amounts[$i] fixed discount applied on cards starting with $discount_patterns[$i]";
            } else {
                PaytabsHelper::log('Paytabs admin: Discount type does not exist', 3);
                return $this;
            }

            $cards[$i]['discount_cards'] = $pattern;
            $cards[$i][$type_key] = $amount;
            $cards[$i]['discount_title'] = $title;
        }

        if (count($cards) > 0) {
            $this->card_discounts = [
                'card_discounts' => $cards
            ];

            if ($flag_set) {
                $this->set50UserDefined(null, null, PaytabsEnum::DISCOUNT_FLAG);
            }
        }

        return $this;
    }
}


/**
 * Holder class, Inherit class PaytabsHolder
 * Holds & Generates the parameters array for the Tokenised payments
 * Members:
 * - Token Info (token & tran_ref)
 */
class PaytabsTokenHolder extends PaytabsExtraDataHolder
{
    /**
     * token
     * tran_ref
     */
    private $token_info;

    /**
     * token
     * tran_ref
     * token_type
     * counter
     * total_count
     */
    private $token_details;


    public function set20Token($tran_ref, $token = null)
    {
        $this->token_info = [
            'tran_ref' => $tran_ref
        ];

        if ($token) {
            $this->token_info['token'] = $token;
        }

        return $this;
    }

    public function set21TokenInfo($tran_ref, $token, $token_type, $counter, $total_count)
    {
        $this->token_details = [];

        $details = [];

        if ($tran_ref) {
            $details = ['tran_ref' => $tran_ref];
        }

        if ($token) {
            $details['token'] = $token;
        }

        if ($token_type) {
            $details['token_type'] = $token_type;
        }
        if (!is_null($counter)) {
            $details['counter'] = $counter;
        }
        if (!is_null($total_count)) {
            $details['total_count'] = $total_count;
        }

        if (count($details) > 0) {
            $this->token_details['token_info'] = $details;
        }

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        if ($this->token_details) {
            $all = array_merge($all, $this->token_details);
        } else if ($this->token_info) {
            $all = array_merge($all, $this->token_info);
        }

        return $all;
    }
}


/**
 * Holder class, Inherit class PaytabsBasicHolder
 * Holds & Generates the parameters array for the Managed form payments
 * Members:
 * - Payment token
 */
class PaytabsManagedFormHolder extends PaytabsBasicHolder
{
    /**
     * payment_token
     */
    private $payment_token;


    public function set30PaymentToken($payment_token)
    {
        $this->payment_token = [
            'payment_token' => $payment_token
        ];

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->payment_token);

        return $all;
    }
}


/**
 * Holder class, Inherit class PaytabsBasicHolder
 * Holds & Generates the parameters array for the Own form payments
 * Members:
 * - Card Info (pan, cvv, expiry_year, expiry_month)
 */
class PaytabsOwnFormHolder extends PaytabsBasicHolder
{
    /**
     * pan
     * cvv
     * expiry_year
     * expiry_month
     */
    private $card_details;


    public function set40CardDetails($pan, $expiry_year, $expiry_month, $cvv = null)
    {
        $card_info = [
            'pan' => $pan,
            'expiry_year'  => (int) $expiry_year,
            'expiry_month' => (int) $expiry_month,
        ];

        if ($cvv) {
            $card_info['cvv'] = $cvv;
        }

        $this->card_details = [
            'card_details' => $card_info
        ];

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->card_details);

        return $all;
    }
}


/**
 * Holder class, Inherit class PaytabsBasicHolder
 * Holds & Generates the parameters array for the ApplePay form payments
 * Members:
 * - apple_pay_token
 */
class PaytabsApplePayHolder extends PaytabsBasicHolder
{
    /**
     * apple_pay_token
     */
    private $apple_pay_token;


    public function set50ApplePay($apple_pay_token)
    {
        $this->apple_pay_token = [
            'apple_pay_token' => $apple_pay_token
        ];

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->apple_pay_token);

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
 * Members:
 * - Transaction ID
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
    const GROUP_CARDS = 'cards';
    const GROUP_CARDS_INTERNATIONAL = 'cards_international';
    const GROUP_TOKENIZE = 'tokenise';
    const GROUP_AUTH_CAPTURE = 'auth_capture';
    const GROUP_REFUND = 'refund';
    const GROUP_IFRAME = 'iframe';
    const GROUP_PENDING = 'payment_request';

    const PAYMENT_TYPES = [
        '0'  => ['name' => 'all', 'title' => 'PayTabs - All', 'currencies' => null, 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND, PaytabsApi::GROUP_PENDING]],
        '1'  => ['name' => 'stcpay', 'title' => 'PayTabs - StcPay', 'currencies' => ['SAR'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '2'  => ['name' => 'stcpayqr', 'title' => 'PayTabs - StcPay(QR)', 'currencies' => ['SAR'], 'groups' => []],
        '3'  => ['name' => 'applepay', 'title' => 'PayTabs - ApplePay', 'currencies' => null, 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_REFUND]],
        '4'  => ['name' => 'omannet', 'title' => 'PayTabs - OmanNet', 'currencies' => ['OMR'], 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '5'  => ['name' => 'mada', 'title' => 'PayTabs - mada', 'currencies' => ['SAR'], 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '6'  => ['name' => 'creditcard', 'title' => 'PayTabs - CreditCard', 'currencies' => null, 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_CARDS_INTERNATIONAL, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '7'  => ['name' => 'sadad', 'title' => 'PayTabs - Sadad', 'currencies' => ['SAR'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_PENDING]],
        // '8'  => ['name' => 'fawry', 'title' => 'PayTabs - @Fawry', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND, PaytabsApi::GROUP_PENDING]],
        '9'  => ['name' => 'knet', 'title' => 'PayTabs - KnPay', 'currencies' => ['KWD', 'USD'], 'groups' => [PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_REFUND]],
        '10' => ['name' => 'amex', 'title' => 'PayTabs - Amex', 'currencies' => ['AED', 'SAR', 'USD'], 'groups' => [PaytabsApi::GROUP_TOKENIZE, PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_CARDS_INTERNATIONAL, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '11' => ['name' => 'valu', 'title' => 'PayTabs - valU', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '12' => ['name' => 'meeza', 'title' => 'PayTabs - Meeza', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '13' => ['name' => 'meezaqr', 'title' => 'PayTabs - Meeza (QR)', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '14' => ['name' => 'unionpay', 'title' => 'PayTabs - UnionPay', 'currencies' => ['AED'], 'groups' => [PaytabsApi::GROUP_AUTH_CAPTURE, PaytabsApi::GROUP_REFUND]],
        '15' => ['name' => 'samsungpay', 'title' => 'PayTabs - SamsungPay', 'currencies' => ['AED', 'SAR'], 'groups' => [PaytabsApi::GROUP_REFUND]],
        '16' => ['name' => 'knetdebit', 'title' => 'PayTabs - KnPay (Debit)', 'currencies' => ['KWD'], 'groups' => [PaytabsApi::GROUP_REFUND]],
        '17' => ['name' => 'knetcredit', 'title' => 'PayTabs - KnPay (Credit)', 'currencies' => ['KWD'], 'groups' => [PaytabsApi::GROUP_REFUND]],
        '18' => ['name' => 'aman', 'title' => 'PayTabs - Aman', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_PENDING]],
        '19' => ['name' => 'urpay', 'title' => 'PayTabs - UrPay', 'currencies' => ['SAR'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '20' => ['name' => 'paypal', 'title' => 'PayTabs - PayPal', 'currencies' => ['AED', 'EGP', 'USD', 'EUR', 'GPB', 'HKD', 'JPY'], 'groups' => [PaytabsApi::GROUP_REFUND]],
        '21' => ['name' => 'installment', 'title' => 'PayTabs - Installment', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_IFRAME]],
        '22' => ['name' => 'touchpoints', 'title' => 'PayTabs - Touchpoints', 'currencies' => ['AED'], 'groups' => [PaytabsApi::GROUP_CARDS, PaytabsApi::GROUP_IFRAME]],
        '23' => ['name' => 'forsa', 'title' => 'PayTabs - Forsa', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME]],
        '24' => ['name' => 'tabby', 'title' => 'PayTabs - Tabby', 'currencies' => ['AED', 'SAR'], 'groups' => []],
        '25' => ['name' => 'souhoola', 'title' => 'PayTabs - Souhoola', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '26' => ['name' => 'amaninstallments', 'title' => 'PayTabs - Aman installments', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '27' => ['name' => 'tamara', 'title' => 'PayTabs - Tamara', 'currencies' => ['AED', 'SAR'], 'groups' => [PaytabsApi::GROUP_IFRAME, PaytabsApi::GROUP_REFUND]],
        '28' => ['name' => 'halan', 'title' => 'PayTabs - Halan', 'currencies' => ['EGP'], 'groups' => [PaytabsApi::GROUP_IFRAME]],
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
        'IRQ' => [
            'title' => 'Iraq',
            'endpoint' => 'https://secure-iraq.paytabs.com/'
        ],
        'PSE' => [
            'title' => 'Palestine',
            'endpoint' => 'https://secure-palestine.paytabs.com/'
        ],
        'KWT' => [
            'title' => 'Kuwait',
            'endpoint' => 'https://secure-kuwait.paytabs.com/'
        ],
        'GLOBAL' => [
            'title' => 'Global',
            'endpoint' => 'https://secure-global.paytabs.com/'
        ],
        // 'DEMO' => [
        //     'title' => 'Demo',
        //     'endpoint' => 'https://paypage.paytabs.com/'
        // ],
    ];

    const URL_REQUEST = 'payment/request';
    const URL_QUERY   = 'payment/query';

    const URL_TOKEN_QUERY  = 'payment/token';
    const URL_TOKEN_DELETE = 'payment/token/delete';

    const URL_INQUIRY_VALU = 'payment/info/valu/inquiry';

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

    /**
     * Merge and return all Currency codes available in the payment methods list
     */
    public static function getCurrencies()
    {
        $currencies = [];
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            $_currencies = $value['currencies'];
            if ($_currencies) {
                $_currencies = array_filter($_currencies);
                $currencies = array_merge($currencies, $_currencies);
            }
        }
        $currencies = array_unique($currencies);
        return $currencies;
    }

    public static function getEndpoint($region)
    {
        $endpoint = self::BASE_URLS[$region]['endpoint'];
        return $endpoint;
    }

    public static function getInstance($region, $merchant_id, $key)
    {
        if (self::$instance == null) {
            self::$instance = new PaytabsApi($region, $merchant_id, $key);
        }

        return self::$instance;
    }

    private function __construct($region, $profile_id, $server_key)
    {
        if (array_key_exists($region, self::BASE_URLS)) {
            $this->base_url = self::BASE_URLS[$region]['endpoint'];
        } else {
            PaytabsHelper::log("Paytabs Admin: Region {$region} is not valid", 2);
        }
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

        $isTokenize =
            $values['tran_class'] == PaytabsEnum::TRAN_CLASS_RECURRING
            || array_key_exists('payment_token', $values)
            || array_key_exists('apple_pay_token', $values)
            || array_key_exists('card_details', $values)
            || (array_key_exists('token', $values) || array_key_exists('token_info', $values));
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

    function inquiry_valu($params)
    {
        $res1 = $this->sendRequest(self::URL_INQUIRY_VALU, $params);

        $res = json_decode($res1);

        $res->success = false;

        if (isset($res->valuResponse, $res->valuResponse->responseCode)) {
            if ($res->valuResponse->responseCode == 0) {
                $res->success = true;
            }
        }

        return $res;
    }
    //

    function is_valid_redirect($post_values)
    {
        if (empty($post_values) || !array_key_exists('signature', $post_values)) {
            return false;
        }

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


    function is_valid_ipn($data, $signature, $serverKey = false)
    {
        if ($serverKey) {
            $server_key = $serverKey;
        } else {
            $server_key = $this->server_key;
        }

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

    public function read_response($is_ipn)
    {
        if ($is_ipn) {
            // $param_tranRef = 'tran_ref';
            // $param_cartId = 'cart_id';

            $response = file_get_contents('php://input');
            $data = json_decode($response);

            $headers = getallheaders();
            // Lower case all keys
            $headers = array_change_key_case($headers);

            $signature = @$headers['signature'] ?? '';
            // $client_key = $headers['Client-Key'];

            $is_valid = $this->is_valid_ipn($response, $signature, false);
        } else {
            // $param_tranRef = 'tranRef';
            // $param_cartId = 'cartId';

            $data = filter_input_array(INPUT_POST);

            $is_valid = $this->is_valid_redirect($data);
        }

        if (!$is_valid) {
            $hashed_key = explode('-', @$this->server_key ?? '')[0];
            PaytabsHelper::log("Paytabs Admin: Invalid Signature ({$hashed_key}, {$signature})", 3);
            return false;
        }

        $response_data = $is_ipn ? $this->enhanceVerify($data) : $this->enhanceReturn($data);

        return $response_data;
    }

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
        } else if (isset($_paypage->code)) {
            $_paypage->success = false;
        } else {
            $_paypage->success = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);
            $_paypage->is_redirect = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);
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
                $_verify->success = PaytabsEnum::TranStatusIsSuccess($verify->payment_result->response_status);
                $_verify->is_on_hold = PaytabsEnum::TranStatusIsOnHold($verify->payment_result->response_status);
                $_verify->is_pending = PaytabsEnum::TranStatusIsPending($verify->payment_result->response_status);
                $_verify->is_expired = PaytabsEnum::TranStatusIsExpired($verify->payment_result->response_status);

                $_verify->response_code = $verify->payment_result->response_code;
            } else {
                $_verify->success = false;
            }
            $_verify->message = $verify->payment_result->response_message;
        }

        if (!isset($_verify->is_on_hold)) {
            $_verify->is_on_hold = false;
        }

        if (!isset($_verify->is_pending)) {
            $_verify->is_pending = false;
        }

        if (!isset($_verify->is_expired)) {
            $_verify->is_expired = false;
        }

        $_verify->reference_no = @$verify->cart_id;
        $_verify->transaction_id = @$verify->tran_ref;

        $_verify->failed = !($_verify->success || $_verify->is_on_hold || $_verify->is_pending);

        return $_verify;
    }

    public function enhanceReturn($return_data)
    {
        $_verify = $return_data;

        if (!$return_data) {
            $_verify = new stdClass();
            $_verify->success = false;
            $_verify->is_on_hold = false;
            $_verify->is_pending = false;
            $_verify->message = 'Verifying paytabs payment failed (locally)';
        } else {
            $_verify = (object)$return_data;

            $response_status = $return_data['respStatus'];

            $_verify->success = PaytabsEnum::TranStatusIsSuccess($response_status);
            $_verify->is_on_hold = PaytabsEnum::TranStatusIsOnHold($response_status);
            $_verify->is_pending = PaytabsEnum::TranStatusIsPending($response_status);

            $_verify->message = $return_data['respMessage'];

            $_verify->transaction_id = $return_data['tranRef'];
            $_verify->reference_no = $return_data['cartId'];
        }

        $_verify->failed = !($_verify->success || $_verify->is_on_hold || $_verify->is_pending);

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
                $_refund->success = PaytabsEnum::TranStatusIsSuccess($refund->payment_result->response_status);
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

        $values['profile_id'] = (int) $this->profile_id;
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
