<?php

namespace Paytabscom\Laravel_paytabs\Services;

use Illuminate\Http\Request;

class IpnRequest
{
    private $request;
    private $clientKey;


    public function __construct(Request $httpRequest) {
        $this->createIpnRequest($httpRequest);
    }

    /**
     * create an object by extracting params received from: callback request or IPN Default Web request from the payment gateway after payment is processed
     */
    private function createIpnRequest($httpRequest){
        //verify that it is a valid callback request\IPN Default Web request
        if($this->isValidIPNRequest($httpRequest)){
            //update the cart payment status
            $content= $httpRequest->getContent();
            $this->request= json_decode($content, false, 3);
            $this->clientKey= $httpRequest->header('client-key');

        }else{
            throw new BadRequestException('invalid callback\IPN request');
        }
    }

    /**
     * process of validating an IPN basic web request is the same as process of validating a IPN default web request
     */
    public static function isValidIPNBasicRequest($httpRequest){
        $this->isValidIPNRequest($httpRequest);
    }

    /**
     * verify that it is a valid callback request or IPN Default Web request
     */
    private function isValidIPNRequest($httpRequest){
        $signature= $httpRequest->header('signature');
        $content= $httpRequest->getContent(); //get the request raw content
        new \Paytabscom\Laravel_paytabs\paytabs_core(); //this is a hack just to be able to use `PaytabsApi` class from paytabs_core.php !!!

        $paytabs_api= \Paytabscom\Laravel_paytabs\PaytabsApi::getInstance(config('paytabs.region'), config('paytabs.profile_id'), config('paytabs.server_key'));
        if($paytabs_api->is_valid_ipn($content, $signature)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * get all the IPN request info
    */
    public function getIpnRequestDetails(){
        return $this->request;
    }

    /**
     * return client-key header parameter
     */
    public function getIpnRequestClientKey(){
        return $this->clientKey;
    }

    public function getCartId() {
        return $this->request->cart_id;
    }

    public function getStatus() {
        return $this->request->payment_result->response_status;
    }

    public function getCode() {
        return $this->request->payment_result->response_code;
    }

    public function getMessage() {
        return $this->request->payment_result->response_message;
    }

    public function getTranRef() {
        return $this->request->tran_ref;
    }

}

/**
 * for wrong request exceptions
 */
class BadRequestException extends \Exception{
    
    protected $message;
    
    public function __construct($message) {
        $this->message= $message;
    }
    
    public function __toString(): string {
        return $this->message;
    }
}
