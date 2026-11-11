<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanOverdue extends Notification
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
            ->subject('Library book overdue')
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
        $days = $this->loan->daysOverdue();

        return sprintf(
            '"%s" is %d %s overdue. The fine so far is KES %s.',
            $this->loan->book->title,
            $days,
            $days === 1 ? 'day' : 'days',
            number_format($this->loan->accruedFine()),
        );
    }
}
