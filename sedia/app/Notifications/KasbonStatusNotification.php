<?php

namespace App\Notifications;

use App\Models\EmployeeTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KasbonStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly EmployeeTransaction $transaction, private readonly string $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->transaction->loadMissing('employee:id,name');
        $messages = [
            'submitted' => 'Pengajuan kasbon baru menunggu persetujuan.',
            'approved' => 'Pengajuan kasbon disetujui.',
            'rejected' => 'Pengajuan kasbon ditolak.',
        ];

        return [
            'title' => 'Kasbon '.$this->transaction->employee?->name,
            'message' => $messages[$this->event],
            'url' => '/app/operations/kasbons',
            'transaction_id' => $this->transaction->id,
            'event' => $this->event,
        ];
    }
}
