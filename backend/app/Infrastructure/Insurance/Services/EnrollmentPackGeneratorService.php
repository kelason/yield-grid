<?php

declare(strict_types=1);

namespace App\Infrastructure\Insurance\Services;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\RsbsaStatus;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceProfile;
use App\Domain\Insurance\Services\EnrollmentPackGeneratorInterface;
use App\Infrastructure\Services\BrowsershotPdfRenderer;
use Closure;
use Domain\Farming\Models\Farm;
use Illuminate\Support\Facades\Storage;

/**
 * Renders a pre-filled PCIC enrollment pack into an A4 PDF via headless Chrome.
 *
 * Template is a plain PHP file (Blade is banned project-wide). The pack is an
 * informational summary for the farmer to bring to their MAO — not an official
 * PCIC document.
 */
final class EnrollmentPackGeneratorService implements EnrollmentPackGeneratorInterface
{
    /**
     * @param  Closure(string):string|null  $pdfRenderer  Overrides headless-Chrome rendering (used by tests).
     */
    public function __construct(
        private readonly ?Closure $pdfRenderer = null,
    ) {}

    public function generateEnrollmentPack(InsuranceEnrollment $enrollment): string
    {
        $html = $this->renderTemplate($enrollment);

        $path = "enrollment-packs/{$enrollment->user_id}/{$enrollment->id}.pdf";

        Storage::disk('local')->put($path, $this->renderer()->render($html));

        return $path;
    }

    private function renderer(): BrowsershotPdfRenderer
    {
        return new BrowsershotPdfRenderer($this->pdfRenderer);
    }

    private function renderTemplate(InsuranceEnrollment $enrollment): string
    {
        $pack = $this->buildPackData($enrollment);

        ob_start();

        include resource_path('pdf-templates/enrollment-pack.php');

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPackData(InsuranceEnrollment $enrollment): array
    {
        $user = $enrollment->user;
        $plot = $enrollment->plot;
        $farm = $plot?->farm;
        $profile = InsuranceProfile::where('user_id', $enrollment->user_id)->first();

        $areaHa = $plot !== null ? (float) $plot->calculated_area : 0.0;

        return [
            'farmer_name' => (string) $user->name,
            'farmer_email' => (string) $user->email,
            'farmer_phone' => (string) ($user->phone ?? '—'),
            'rsbsa_number' => $profile !== null && $profile->rsbsa_number !== null
                ? (string) $profile->rsbsa_number
                : 'Not provided',
            'rsbsa_registered' => $profile !== null && $profile->rsbsa_status === RsbsaStatus::REGISTERED,
            'farm_name' => $farm instanceof Farm ? (string) $farm->name : '—',
            'farm_location' => $this->farmLocation($farm),
            'plot_name' => $plot !== null ? (string) $plot->name : '—',
            'plot_area_ha' => $areaHa,
            'plot_soil_type' => $plot?->soil_type !== null ? $plot->soil_type->value : '—',
            'program_label' => ucfirst($enrollment->program->value),
            'season_label' => ucfirst($enrollment->season->value).' season '.$enrollment->season_year,
            'estimated_coverage_php' => $areaHa * InsuranceConstants::COVERAGE_PER_HECTARE_PHP,
            'coverage_per_hectare_php' => InsuranceConstants::COVERAGE_PER_HECTARE_PHP,
            'generated_at' => now()->format('F j, Y'),
        ];
    }

    private function farmLocation(?Farm $farm): string
    {
        if (! $farm instanceof Farm) {
            return '—';
        }

        $parts = array_filter([(string) ($farm->city ?? ''), (string) ($farm->state ?? '')]);

        return $parts === [] ? '—' : implode(', ', $parts);
    }
}
