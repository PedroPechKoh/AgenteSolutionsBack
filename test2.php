<?php
$jobs = \App\Models\WorkOrder::with(['networkQuotes'])->limit(1)->get();
echo json_encode($jobs[0]);
