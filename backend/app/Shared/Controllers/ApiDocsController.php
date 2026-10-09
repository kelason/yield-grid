<?php

declare(strict_types=1);

namespace App\Shared\Controllers;

use App\Constants\HttpCode;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

final class ApiDocsController extends Controller
{
    public function ui(Request $request): View|JsonResponse|RedirectResponse
    {
        $this->ensureDocsHost($request);

        // Request paths normalize before route matching, so /docs/ lands here too.
        if ($request->getPathInfo() === '/docs/') {
            return redirect('/docs', HttpCode::MOVED_PERMANENTLY);
        }

        $spec = $this->readSpec();

        if ($spec === null) {
            return $this->missingSpecResponse();
        }

        // Cached UI has no live generator diagnostics; dev-tools would fatal on a missing result.
        Config::set('scramble.dev_tools.enabled', false);

        return view('scramble::docs', [
            'spec' => $spec,
            'config' => Scramble::configure(),
        ]);
    }

    public function spec(Request $request): JsonResponse
    {
        $this->ensureDocsHost($request);

        $spec = $this->readSpec();

        if ($spec === null) {
            return $this->missingSpecResponse();
        }

        return response()->json($spec);
    }

    private function ensureDocsHost(Request $request): void
    {
        $docsHost = Config::get('docs.host');

        if ($docsHost !== null && $request->getHost() !== $docsHost) {
            abort(HttpCode::NOT_FOUND);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readSpec(): ?array
    {
        $path = (string) Config::get('docs.spec_path');

        if (! File::exists($path)) {
            return null;
        }

        /** @var array<string, mixed> */
        return json_decode((string) File::get($path), true);
    }

    private function missingSpecResponse(): JsonResponse
    {
        return response()->json(
            ['message' => 'API docs not generated yet.'],
            HttpCode::SERVICE_UNAVAILABLE
        );
    }
}
