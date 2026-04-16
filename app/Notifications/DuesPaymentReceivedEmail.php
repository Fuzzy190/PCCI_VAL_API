<?php

namespace App\Notifications;

use App\Models\DuesPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email notification for payment received confirmation
 * 
 * SET ASIDE: Enable by uncommenting "implements ShouldQueue" to queue emails
 */
class DuesPaymentReceivedEmail extends Notification
{
    use Queueable;

    protected $payment;

    public function __construct(DuesPayment $payment)
    {
        $this->payment = $payment;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;
        $member = $payment->member;
        $due = $payment->membershipDue;

        return (new MailMessage)
            ->subject('Payment Received - Confirmation')
            ->greeting("Hello {$member->applicant->contact_person_name},")
            ->line("We have received your payment. Thank you!")
            ->line("**Payment Details:**")
            ->line("- Amount: ₱" . number_format($payment->amount, 2))
            ->line("- Receipt Number (OR): {$payment->or_number}")
            ->line("- Payment Date: {$payment->payment_date->format('F d, Y')}")
            ->line("- Payment Method: " . ucfirst($payment->payment_method))
            ->line("- For: Membership Dues {$due->due_year}")
            ->line("")
            ->line("Your membership is now active for this period.")
            ->line("Thank you for your continued membership!")
            ->salutation('Best regards, PCCI');
    }
}
