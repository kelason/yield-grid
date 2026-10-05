<?php

declare(strict_types=1);

namespace App\Insurance\Mail;

use App\Domain\Insurance\Enums\ReminderType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Farmer-facing insurance reminders. Rendered from a plain PHP template
 * (Blade is banned project-wide). English only — per-farmer locale
 * preference is deferred; the in-app API serves translation keys instead.
 */
final class InsuranceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly ReminderType $type,
        public readonly array $meta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectFor($this->type));
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderTemplate());
    }

    private function subjectFor(ReminderType $type): string
    {
        return match ($type) {
            ReminderType::ENROLLMENT_WINDOW => 'PCIC enrollment window opening soon',
            ReminderType::NOTICE_OF_LOSS_DEADLINE => 'File your PCIC Notice of Loss before the deadline',
            ReminderType::CLAIM_FOLLOWUP => 'Follow up on your PCIC claim',
            ReminderType::RENEWAL => 'Your crop insurance is expiring soon',
        };
    }

    private function renderTemplate(): string
    {
        $mail = [
            'title' => $this->subjectFor($this->type),
            'lines' => $this->linesFor($this->type, $this->meta),
            'cta_url' => rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/').'/dashboard/insurance',
            'cta_label' => 'Open Crop Insurance',
        ];

        ob_start();

        include resource_path('mail-templates/insurance-reminder.php');

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return list<string>
     */
    private function linesFor(ReminderType $type, array $meta): array
    {
        return match ($type) {
            ReminderType::ENROLLMENT_WINDOW => [
                'The '.($meta['season'] ?? '').' '.$this->metaYear($meta).' planting window ('.($meta['window_label'] ?? '').') is opening soon.',
                'Prepare your RSBSA stub and requirements, then complete your enrollment guide in YieldGrid.',
            ],
            ReminderType::NOTICE_OF_LOSS_DEADLINE => [
                'Your written Notice of Loss for the '.($meta['loss_date'] ?? '').' crop damage must reach PCIC by '.($meta['deadline'] ?? '').'.',
                'File through your Municipal Agriculturist Office, then record the filing in YieldGrid.',
            ],
            ReminderType::CLAIM_FOLLOWUP => [
                'Your claim has seen no progress lately. Check with your MAO or PCIC regional office for its status.',
                'Update the claim stage in YieldGrid so your reminders stay accurate.',
            ],
            ReminderType::RENEWAL => [
                'Your crop insurance coverage expires on '.($meta['expires_at'] ?? '').'.',
                'Start a new enrollment for the coming season so you stay protected.',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function metaYear(array $meta): string
    {
        return (string) ($meta['season_year'] ?? '');
    }
}
