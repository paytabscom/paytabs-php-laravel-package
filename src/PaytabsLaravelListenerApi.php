<?php

namespace Paytabscom\Laravel_paytabs;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Paytabscom\Laravel_paytabs\IpnRequest;

class PaytabsLaravelListenerApi extends BaseController
{

    /**
     * RESTful callable action able to receive: callback request\IPN Default Web request from the payment gateway after payment is processed
     */
    public function paymentIPN(Request $request){
        try{
            $ipnRequest= new IpnRequest($request);

            $callback = config('paytabs.callback');
            if(is_object($callback) && method_exists($callback, 'updateCartByIPN') ){
                $callback->updateCartByIPN($ipnRequest);
            }
            $response= 'valid IPN request. Cart updated';
            return response($response, 200)
                ->header('Content-Type', 'text/plain');
        }catch(\Exception $e){
            return response($e, 400)
                ->header('Content-Type', 'text/plain');        
        }
    }

}