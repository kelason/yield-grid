<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Parses DA Bantay Presyo price-table fragments.
 *
 * Verified site contract (Oct 2026): each category page POSTs
 * {commodity, region} to tbl_price_get_comm_price_{page}.php and injects
 * the returned <tr> rows into its table body. Two layouts are handled:
 * market columns (averaged) and trailing date columns (latest wins).
 * Bantay Presyo monitors retail prices, so every row is retail tier.
 */
final class DaPriceTableParser
{
    private const METADATA_HEADER_PATTERN = '/change|%|previous|prev|trend|remarks|variance/i';

    private const SUMMARY_ROW_PATTERN = '/^(total|average|overall|grand)\b/i';

    private const DATE_HINT_PATTERN = '/\d{4}|\d{1,2}[\/-]\d{1,2}|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec/i';

    private const MARKET_LABEL = 'DA monitored average';

    /**
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    public static function parse(?string $headerHtml, ?string $priceHtml, string $regionCode): array
    {
        if (trim((string) $priceHtml) === '') {
            return [];
        }

        try {
            $headers = self::firstRowCells((string) $headerHtml);
            $dateColumn = self::latestDateColumn($headers);

            $rows = [];

            foreach (self::tableRows((string) $priceHtml) as $cells) {
                if (count($cells) < 2) {
                    continue;
                }

                $name = trim($cells[0]);

                if ($name === '' || Str::slug($name) === '' || preg_match(self::SUMMARY_ROW_PATTERN, $name) === 1) {
                    continue;
                }

                $parsed = $dateColumn !== null
                    ? self::parseDatedRow($cells, $headers, $dateColumn)
                    : self::parseMarketRow($cells, $headers);

                if ($parsed === null) {
                    continue;
                }

                [$price, $observedAt] = $parsed;

                $rows[] = [
                    'crop_slug' => Str::slug($name),
                    'crop_display_name' => $name,
                    'tier' => PriceTier::RETAIL->value,
                    'price_per_kg' => $price,
                    'currency' => 'PHP',
                    'region_code' => $regionCode,
                    'market_name' => self::MARKET_LABEL,
                    'source' => PriceSource::DA_BANTAY_PRESYO->value,
                    'observed_at' => $observedAt,
                ];
            }

            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    private static function firstRowCells(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $rows = self::tableRows("<table><tr>{$html}</tr></table>");

        return $rows[0] ?? [];
    }

    /**
     * @return list<list<string>>
     */
    private static function tableRows(string $html): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $rows = [];

        /** @var \DOMElement $row */
        foreach ($xpath->query('//tr') as $row) {
            $cells = [];

            /** @var \DOMElement $cell */
            foreach ($xpath->query('./th|./td', $row) as $cell) {
                $cells[] = trim($cell->textContent ?? '');
            }

            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>  $headers
     */
    private static function latestDateColumn(array $headers): ?int
    {
        $latest = null;

        foreach ($headers as $index => $header) {
            if ($index === 0 || preg_match(self::DATE_HINT_PATTERN, $header) !== 1) {
                continue;
            }

            try {
                Carbon::parse($header);
                $latest = $index;
            } catch (\Throwable) {
                continue;
            }
        }

        return $latest;
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $headers
     * @return ?array{float, string}
     */
    private static function parseDatedRow(array $cells, array $headers, int $dateColumn): ?array
    {
        if (! isset($cells[$dateColumn])) {
            return null;
        }

        $price = self::parsePrice($cells[$dateColumn]);

        if ($price === null) {
            return null;
        }

        try {
            $observedAt = Carbon::parse($headers[$dateColumn])->toDateString();
        } catch (\Throwable) {
            $observedAt = now()->toDateString();
        }

        return [$price, $observedAt];
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $headers
     * @return ?array{float, string}
     */
    private static function parseMarketRow(array $cells, array $headers): ?array
    {
        $prices = [];

        foreach (array_slice($cells, 1) as $offset => $cell) {
            $header = $headers[$offset + 1] ?? '';

            if ($header !== '' && preg_match(self::METADATA_HEADER_PATTERN, $header) === 1) {
                continue;
            }

            $price = self::parsePrice($cell);

            if ($price !== null) {
                $prices[] = $price;
            }
        }

        if ($prices === []) {
            return null;
        }

        return [round(array_sum($prices) / count($prices), 2), now()->toDateString()];
    }

    private static function parsePrice(string $cell): ?float
    {
        $cleaned = str_replace(['₱', ',', '/kg', 'per kg', 'PHP', 'php'], '', $cell);

        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $cleaned, $range) === 1) {
            $price = ((float) $range[1] + (float) $range[2]) / 2;

            return $price > 0 ? round($price, 2) : null;
        }

        if (preg_match('/\d+(?:\.\d+)?/', $cleaned, $matches) !== 1) {
            return null;
        }

        $price = (float) $matches[0];

        return $price > 0 ? round($price, 2) : null;
    }
}
