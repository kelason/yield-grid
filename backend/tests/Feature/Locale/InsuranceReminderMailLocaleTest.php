<?php

declare(strict_types=1);

use App\Domain\Insurance\Enums\ReminderType;
use App\Insurance\Mail\InsuranceReminderMail;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function enrollmentMeta(): array
{
    return [
        'season' => 'Wet',
        'season_year' => '2026',
        'program' => 'Rice',
        'window_label' => 'Nov-Dec',
    ];
}

it('renders the Cebuano subject and interpolated body lines', function (): void {
    $mail = new InsuranceReminderMail(
        ReminderType::ENROLLMENT_WINDOW,
        enrollmentMeta(),
        'ceb'
    );

    expect($mail->envelope()->subject)->toContain('RSBSA');

    $html = $mail->render();

    expect($html)->toContain('Wet')
        ->and($html)->toContain('2026')
        ->and($html)->toContain('Rice')
        ->and($html)->toContain('Nov-Dec')
        ->and($html)->not->toContain('is opening soon');
});

it('keeps the exact English subject', function (): void {
    $mail = new InsuranceReminderMail(
        ReminderType::ENROLLMENT_WINDOW,
        enrollmentMeta(),
        'en'
    );

    expect($mail->envelope()->subject)->toBe('PCIC enrollment window opening soon');
});

it('renders the queue-time locale even after the user changes preference', function (): void {
    $farmer = User::factory()->create(['locale' => 'ceb']);
    $mail = new InsuranceReminderMail(
        ReminderType::ENROLLMENT_WINDOW,
        enrollmentMeta(),
        $farmer->locale
    );

    $farmer->update(['locale' => 'tl']);

    expect($mail->envelope()->subject)->toContain('RSBSA');
    expect($mail->render())->not->toContain('is opening soon');
});
