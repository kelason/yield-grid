<?php

declare(strict_types=1);

namespace App\Insurance\Mail;

use App\Constants\LocaleConstants;
use App\Domain\Insurance\Enums\ReminderType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Farmer-facing insurance reminders. Rendered from a plain PHP template
 * (Blade is banned project-wide) in the recipient's locale, captured at
 * construction so queue delays never change the language. The in-app API
 * serves translation keys instead.
 */
final class InsuranceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private readonly string $recipientLocale;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly ReminderType $type,
        public readonly array $meta,
        string $locale,
    ) {
        $this->recipientLocale = in_array($locale, LocaleConstants::SUPPORTED, true)
            ? $locale
            : LocaleConstants::DEFAULT;
        $this->locale($this->recipientLocale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectFor($this->type));
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderTemplate());
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function line(string $key, array $replace = []): string
    {
        return __($key, $replace, $this->recipientLocale);
    }

    private function subjectFor(ReminderType $type): string
    {
        return $this->line('insurance_mail.subjects.'.$type->value);
    }

    private function renderTemplate(): string
    {
        $mail = [
            'title' => $this->subjectFor($this->type),
            'lines' => $this->linesFor($this->type, $this->meta),
            'cta_url' => rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/').'/dashboard/insurance',
            'cta_label' => $this->line('insurance_mail.cta'),
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
        $templates = __('insurance_mail.lines.'.$type->value, [], $this->recipientLocale);

        return array_map(
            fn (string $template) => $this->interpolate($template, $meta),
            is_array($templates) ? array_values($templates) : []
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function interpolate(string $template, array $meta): string
    {
        return preg_replace_callback(
            '/:([a-z_]+)/',
            fn (array $matches) => (string) ($meta[$matches[1]] ?? ''),
            $template
        );
    }
}
