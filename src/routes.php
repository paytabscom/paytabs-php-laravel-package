<?php

Route::post('/paymentIPN', [Paytabscom\Laravel_paytabs\PaytabsLaravelListenerApi::class, 'paymentIPN'])->name('payment_ipn');
