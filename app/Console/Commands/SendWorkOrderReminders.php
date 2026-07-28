<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkOrder;
use App\Models\Service;
use App\Models\User;
use App\Notifications\WorkOrderHourlyReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendWorkOrderReminders extends Command
{
    protected $signature = 'work-orders:send-reminders';
    protected $description = 'Envía notificaciones de recordatorio a los técnicos 1 hora antes de que inicie su servicio u orden de trabajo';

    public function handle()
    {
        $this->info('Buscando trabajos programados dentro de la próxima hora...');
        $now = Carbon::now();
        $targetStart = Carbon::now()->addMinutes(70);

        $count = 0;

        // 1. Work Orders
        $workOrders = WorkOrder::with(['property', 'tecnico', 'technicians'])
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', ['Listo', 'Finalizado', 'Rechazado', 'Cancelado'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$now, $targetStart])
            ->get();

        foreach ($workOrders as $wo) {
            $techniciansToNotify = collect();

            if ($wo->tecnico) {
                $techniciansToNotify->push($wo->tecnico);
            }
            if ($wo->technicians && $wo->technicians->count() > 0) {
                foreach ($wo->technicians as $t) {
                    $techniciansToNotify->push($t);
                }
            }

            $uniqueTechs = $techniciansToNotify->unique('id');

            foreach ($uniqueTechs as $tech) {
                Notification::send($tech, new WorkOrderHourlyReminderNotification($wo, true));
                $count++;
            }

            $wo->reminder_sent_at = now();
            $wo->save();
        }

        // 2. Services
        $services = Service::with(['property', 'technician', 'technicians'])
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', ['Listo', 'Finalizado', 'Rechazado', 'Cancelado'])
            ->whereNotNull('scheduled_start')
            ->whereBetween('scheduled_start', [$now, $targetStart])
            ->get();

        foreach ($services as $srv) {
            $techniciansToNotify = collect();

            if ($srv->technician) {
                $techniciansToNotify->push($srv->technician);
            }
            if ($srv->technicians && $srv->technicians->count() > 0) {
                foreach ($srv->technicians as $t) {
                    $techniciansToNotify->push($t);
                }
            }

            $uniqueTechs = $techniciansToNotify->unique('id');

            foreach ($uniqueTechs as $tech) {
                Notification::send($tech, new WorkOrderHourlyReminderNotification($srv, false));
                $count++;
            }

            $srv->reminder_sent_at = now();
            $srv->save();
        }

        $this->info("Recordatorios enviados correctamente. Total notificados: {$count}");
        Log::info("SendWorkOrderReminders ejecutado: {$count} notificaciones de recordatorio enviadas.");

        return Command::SUCCESS;
    }
}
