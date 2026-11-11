<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReady extends Notification
{
    public function __construct(public Reservation $reservation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your reserved book is ready')
            ->line($this->message())
            ->action('View my library', route('member.dashboard'));
    }

    /**
     * @return array{message: string, book_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'book_id' => $this->reservation->book_id,
        ];
    }

    private function message(): string
    {
        return sprintf(
            '"%s" is ready for you to collect. It will be held until %s.',
            $this->reservation->book->title,
            $this->reservation->expires_at->format('j M Y'),
        );
    }
}
