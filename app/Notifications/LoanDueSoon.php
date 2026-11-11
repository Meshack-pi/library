<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDueSoon extends Notification
{
    public function __construct(public Loan $loan) {}

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
            ->subject('Library book due tomorrow')
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
            'book_id' => $this->loan->book_id,
        ];
    }

    private function message(): string
    {
        return sprintf(
            '"%s" is due back on %s. Return or renew it to avoid a fine.',
            $this->loan->book->title,
            $this->loan->due_at->format('j M Y'),
        );
    }
}
