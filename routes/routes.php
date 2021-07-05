<?php

Route::post('/paymentIPN', [\Paytabscom\Laravel_paytabs\Controllers\PaytabsLaravelListenerApi::class, 'paymentIPN'])->name('payment_ipn');
