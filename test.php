<?php

try {
    $u = App\Models\User::first();
    $q = new App\Models\NetworkQuote(['id'=>1, 'work_order_id'=>1]);
    $u->notify(new App\Notifications\NetworkQuoteRejected($q));
    echo "Success!\n";
} catch(\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
