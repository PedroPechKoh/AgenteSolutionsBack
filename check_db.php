<?php

use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\FinalWorkReport;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- ULTIMOS SERVICIOS ---\n";
$services = Service::orderBy('id', 'desc')->take(5)->get(['id', 'description', 'status']);
foreach ($services as $s) {
    echo "ID: {$s->id} | Desc: {$s->description} | Status: {$s->status}\n";
}

echo "\n--- ULTIMAS ORDENES DE TRABAJO (WorkOrders) ---\n";
$workOrders = WorkOrder::orderBy('id', 'desc')->take(5)->get(['id', 'description', 'status']);
foreach ($workOrders as $w) {
    echo "ID: {$w->id} | Desc: {$w->description} | Status: {$w->status}\n";
}

echo "\n--- ULTIMOS REPORTES FINALES (FinalWorkReports) ---\n";
$reports = FinalWorkReport::orderBy('id', 'desc')->take(5)->get(['id', 'service_id', 'work_order_id', 'folio']);
foreach ($reports as $r) {
    echo "ID: {$r->id} | ServiceID: " . ($r->service_id ?? 'N/A') . " | WorkOrderID: " . ($r->work_order_id ?? 'N/A') . " | Folio: {$r->folio}\n";
}
