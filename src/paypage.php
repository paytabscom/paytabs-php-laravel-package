<?php


namespace Paytabscom\Laravel_paytabs;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;


class paypage
{

    public  $paytabsinit,
        $paytabs_core,
        $paytabs_core_token,
        $paytabs_api,
        $follow_transaction,
        $laravel_version,
        $package_version;
    function __construct()
    {
        $this->paytabsinit = new paytabs_core();
        $this->paytabs_core = new PaytabsRequestHolder();
        $this->paytabs_core_token = new PaytabsTokenHolder();
        $this->paytabs_api = PaytabsApi::getInstance(config('paytabs.region'), config('paytabs.profile_id'), config('paytabs.server_key'));
        $this->follow_transaction = new PaytabsFollowupHolder();
        $this->laravel_version = app()::VERSION;
        $this->package_version = '1.7.1';
    }

    public function sendPaymentCode($code)
    {
        $this->paytabs_core->set01PaymentCode($code);
        return $this;
    }

    public function sendTransaction($transaction,$tran_class)
    {
        $this->paytabs_core->set02Transaction($transaction,$tran_class);
        return $this;
    }

    public function sendCart($cart_id, $amount, $cart_description)
    {
        $this->paytabs_core->set03Cart($cart_id, config('paytabs.currency'), $amount, $cart_description);
        return $this;
    }

    public function sendCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        $this->paytabs_core->set04CustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);
        return $this;
    }
    
    public function sendShippingDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        $this->paytabs_core->set05ShippingDetails(false, $name, $email, $phone, $address, $city, $state, $country, $zip, $ip);
        return $this;
    }

    public function shipping_same_billing()
    {
        $this->paytabs_core->set05ShippingDetails(true);
        return $this;
    }

    public function sendHideShipping($on = false)
    {
        $this->paytabs_core->set06HideShipping($on);
        return $this;
    }

    public function sendURLs($return_url, $callback_url)
    {
        $this->paytabs_core->set07URLs($return_url, $callback_url);
        return $this;
    }

    public function sendLanguage($language)
    {
        $this->paytabs_core->set08Lang($language);
        return $this;
    }

    public function sendFramed($on = false)
    {
        $this->paytabs_core->set09Framed($on);
        return $this;
    }

    public function sendTokinse($on = false)
    {
        $this->paytabs_core->set10Tokenise($on);
        return $this;
    }

    public function sendToken($tran_ref,$token)
    {
        $this->paytabs_core_token->set20Token($tran_ref,$token);
        return $this; 
    }

    public function sendUserDefined(array $user_defined = [])
    {
        $this->paytabs_core->set50UserDefined($user_defined);
        return $this; 
    }

   public function create_pay_page()
    {
        $this->paytabs_core->set99PluginInfo('Laravel',9,'1.7.1');
        $basic_params = $this->paytabs_core->pt_build();
        $token_params = $this->paytabs_core_token->pt_build();
        $pp_params = array_merge($basic_params,$token_params);
        $response = $this->paytabs_api->create_pay_page($pp_params);

       
        if(isset($response->is_redirect) && $response->is_redirect)
        {
            if ($response->success) 
            {
                $redirect_url = $response->redirect_url;
                if (isset($pp_params['framed']) &&  $pp_params['framed'] == true)
                {
                    return $redirect_url;
                }
                return Redirect::to($redirect_url);
            }
            else
            {
                Log::channel('PayTabs')->info(json_encode($response));
                print_r(json_encode($response));
            }
        }

        if(isset($response->is_completed) && $response->is_completed)
        {
            if ($response->success) 
            {
                $data = [
                    'tran_ref' => $response->tran_ref,
                    'previous_tran_ref' => $response->previous_tran_ref
                ];

                return response()->json(['data' => $data], 200);
            }
            else
            {
                Log::channel('PayTabs')->info(json_encode($response));
                print_r(json_encode($response));
            }
        }
       
        Log::channel('PayTabs')->info(json_encode($response));
        print_r(json_encode($response));
      
    }


    public function refund($tran_ref,$order_id,$amount,$refund_reason)
    {
        $this->follow_transaction->set02Transaction(PaytabsEnum::TRAN_TYPE_REFUND)
            ->set03Cart($order_id, config('paytabs.currency'), $amount, $refund_reason)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $refund_params = $this->follow_transaction->pt_build();
        $result = $this->paytabs_api->request_followup($refund_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->paytabs_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_refunded';
            } else {
                $status = 'refunded';
            }
            $data = [
                'tran_ref' => $result->tran_ref,
                'previous_tran_ref' => $result->previous_tran_ref,
                'refunded_amount' => $result->tran_total,
                'status' => $status
            ];
            return response()->json($data, 200);
        } else if ($pending_success) {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: ' . $message);
        }
        else
        {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: '. $message);
        } 

    }

    public function capture($tran_ref,$order_id,$amount,$capture_description)
    {
        $this->follow_transaction->set02Transaction(PaytabsEnum::TRAN_TYPE_CAPTURE)
            ->set03Cart($order_id, config('paytabs.currency'), $amount, $capture_description)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $capture_params = $this->follow_transaction->pt_build();
        $result = $this->paytabs_api->request_followup($capture_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->paytabs_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_captured';
            } else {
                $status = 'captured';
            }

             $data = [
                'tran_ref' => $result->tran_ref,
                'previous_tran_ref' => $result->previous_tran_ref,
                'captured_amount' => $result->tran_total,
                'status' => $status
            ];
            return response()->json($data, 200);

         } else if ($pending_success) {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: ' . $message);
        }
        else
        {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: '. $message);
        } 
    }

    public function void($tran_ref,$order_id,$amount,$void_description)
    {
        $this->follow_transaction->set02Transaction(PaytabsEnum::TRAN_TYPE_VOID)
            ->set03Cart($order_id, config('paytabs.currency'), $amount, $void_description)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $void_params = $this->follow_transaction->pt_build();
        $result = $this->paytabs_api->request_followup($void_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->paytabs_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_voided';
            } else {
                $status = 'voided';
            }
            $data = [
                'tran_ref' => $result->tran_ref,
                'previous_tran_ref' => $result->previous_tran_ref,
                'voided_amount' => $result->tran_total,
                'status' => $status
            ];
            return response()->json($data, 200);
        } else if ($pending_success) {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: ' . $message);
        }
        else
        {
            Log::channel('PayTabs')->info(json_encode($result));
            print_r('something went wrong with integration <br/> paytabs message is: '. $message);
        } 
    }

    public function queryTransaction($tran_ref)
    {
        $transaction = $this->paytabs_api->verify_payment($tran_ref);
        return $transaction;
    }
}

