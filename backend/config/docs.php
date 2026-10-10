<?php

declare(strict_types=1);

return [

    /*
     * Host that serves the public API docs. Null allows all hosts (local dev).
     */
    'host' => env('DOCS_HOST'),

    /*
     * Deploy-time exported OpenAPI spec served by the docs routes.
     */
    'spec_path' => storage_path('app/api-docs/openapi.json'),

];
