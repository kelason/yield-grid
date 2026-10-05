<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Closure;
use Spatie\Browsershot\Browsershot;

/**
 * Shared headless-Chrome HTML→PDF renderer for all generated documents.
 *
 * Chrome and Node module paths come from services.browsershot config so
 * local, CI, and production can each point at their own binaries.
 */
final class BrowsershotPdfRenderer
{
    private const PDF_MARGIN_MM = 10;

    /**
     * @param  Closure(string):string|null  $pdfRenderer  Overrides headless-Chrome rendering (used by tests).
     */
    public function __construct(
        private readonly ?Closure $pdfRenderer = null,
    ) {}

    public function render(string $html): string
    {
        if ($this->pdfRenderer !== null) {
            return ($this->pdfRenderer)($html);
        }

        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(
                top: self::PDF_MARGIN_MM,
                right: self::PDF_MARGIN_MM,
                bottom: self::PDF_MARGIN_MM,
                left: self::PDF_MARGIN_MM
            )
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->setNodeBinary((string) config('services.browsershot.node_binary', 'node'))
            ->setNodeModulePath((string) config('services.browsershot.node_module_path', base_path('node_modules')));

        $this->applyChromeEnvironment($shot);

        return $shot->pdf();
    }

    private function applyChromeEnvironment(Browsershot $shot): void
    {
        $chromePath = config('services.browsershot.chrome_path');

        if (is_string($chromePath) && $chromePath !== '') {
            $shot->setChromePath($chromePath);
        }

        if ((bool) config('services.browsershot.no_sandbox', false)) {
            $shot->noSandbox();
        }
    }
}
