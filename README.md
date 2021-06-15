Laravel PayTabs PT2

Description
-----------
This Package provides integration with the PayTabs payment gateway.

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* usage

INTRODUCTION
------------
This Package integrates PayTabs online payments into
the Laravel Framework starts from version 5.8 - 8.x.

REQUIREMENTS
------------
This Package requires no external dependencies.

INSTALLATION
------------
- composer require paytabscom/laravel_paytabs

CONFIGURATION
-------------
* composer dump-autoload

* Go to _config/app.php_ and in the providers array add

        Paytabscom\Laravel_paytabs\PaypageServiceProvider::class,

* Create the package config file:

        php artisan vendor:publish --tag=paytabs

* Go to _config/logging.php_ and in the channels array add
  
      'PayTabs' => [
      'driver' => 'single',
      'path' => storage_path('logs/paytabs.log'),
      'level' => 'info',
      ],
  
* In _config/paytabs.php_ add your merchant info.

**Important Hint:**
  you can pass your merchant info in the environment file with the same key names mentioned in the _config/paytabs.php_ file.
  This value will be returned if no environment variable exists for the given key. 
  

Usage
-------------

* create pay page

        use Paytabscom\Laravel_paytabs\Facades\paypage;

        $pay= paypage::sendPaymentCode('all')
               ->sendTransaction('sale')
                ->sendCart(10,1000,'test')
               ->sendCustomerDetails('Walaa Elsaeed', 'w.elsaeed@paytabs.com', '01092540925', 'test', 'Nasr City', 'Cairo', 'Egypt', '1234','100.279.20.10')
               ->sendShippingDetails('Walaa Elsaeed', 'w.elsaeed@paytabs.com', '01092540925', 'test', 'Nasr City', 'Cairo', 'Egypt', '1234','100.279.20.10')
               ->sendURLs('return_url', 'callback_url')
               ->sendLanguage('en')
               ->create_pay_page();
        return $pay;
  
* if you want to pass the shipping address as same as billing address you can use
        
        ->sendShippingDetails('same as billing')

* if you want to hide the shipping address you can use 
  
        ->sendHideShipping(true);

* if you want to use iframe option instead of redirection you can use
  
        ->sendFramed(true);


* refund (you can use this function to both refund and partially refund)

        $refund = Paypage::refund('tran_ref','order_id','amount','refund_reason');
        return $refund;




* Auth

        pay= Paypage::sendPaymentCode('all')
               ->sendTransaction('Auth')
                ->sendCart(10,1000,'test')
               ->sendCustomerDetails('Walaa Elsaeed', 'w.elsaeed@paytabs.com', '01092540925', 'test', 'Nasr City', 'Cairo', 'Egypt', '1234','100.279.20.10')
               ->sendShippingDetails('Walaa Elsaeed', 'w.elsaeed@paytabs.com', '01092540925', 'test', 'Nasr City', 'Cairo', 'Egypt', '1234','100.279.20.10')
               ->sendURLs('return_url', 'callback_url')
               ->sendLanguage('en')
               ->create_pay_page();
        return $pay;


* capture (the tran_ref is the tran_ref of the Auth transaction you need to capture it.
  
  you can use this function to both capture and partially capture.)

         $capture = Paypage::capture('tran_ref','order_id','amount','capture description'); 
         return $capture;



* void (the tran_ref is the tran_ref of the Auth transaction you need to void it.
  
  you can use this function to both capture and partially capture)

        $void = Paypage::void('tran_ref','order_id','amount','void description');
        return $void
    

* transaction details

        $transaction = Paypage::queryTransaction('tran_ref');
        return $transaction;

* if you face any error you will find it logged in: _storage/logs/paytabs.log_

To Receive payment status update
--------------------------------

PayTabs payment gateway provides means to notify merchant system with payment result once transaction processing was completed so that merchant system can update the transaction respective cart.

1- While creating a pay page, if a URL is passed as the second argument to _sendURLs_ method, that URL will receive an HTTP Post request with the payment result. For more about callback check: **merchant dashboard** > **Developers** > **Transaction API**.

2- Second means is to configure IPN notification from merchant dashboard. For more details about how to configure IPN request and its different formats check: **merchant dashboard** > **Developers** > **Service Types**.

