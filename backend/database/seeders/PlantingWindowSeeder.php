<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\PlantingWindow;
use Illuminate\Database\Seeder;

final class PlantingWindowSeeder extends Seeder
{
    /**
     * National double-cropping windows (PhilRice): wet planting May–Jul,
     * dry planting Nov–Jan. Caraga override: wet cropping Jul–Dec,
     * dry cropping Jan–Jun (PhilRice Agusan 2017).
     */
    public function run(): void
    {
        $rows = array_merge($this->nationalRows(), $this->caragaRows());

        foreach ($rows as $row) {
            PlantingWindow::updateOrCreate(
                [
                    'region_code' => $row['region_code'],
                    'program' => $row['program'],
                    'season' => $row['season'],
                ],
                $row,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function nationalRows(): array
    {
        $rows = [];

        foreach (InsuranceProgram::cases() as $program) {
            foreach (Season::cases() as $season) {
                $rows[] = $this->nationalRow($program, $season);
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function caragaRows(): array
    {
        $rows = [];

        foreach (InsuranceProgram::cases() as $program) {
            foreach (Season::cases() as $season) {
                $rows[] = $this->caragaRow($program, $season);
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function caragaRow(InsuranceProgram $program, Season $season): array
    {
        $wet = $season === Season::WET;

        return [
            'region_code' => '160000000',
            'program' => $program,
            'season' => $season,
            'window_start_month' => $wet ? 6 : 12,
            'window_start_day' => 15,
            'window_end_month' => $wet ? 8 : 2,
            'window_end_day' => $wet ? 31 : 28,
            'source' => 'philrice-agusan-2017',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nationalRow(InsuranceProgram $program, Season $season): array
    {
        $wet = $season === Season::WET;

        return [
            'region_code' => 'NATIONAL',
            'program' => $program,
            'season' => $season,
            'window_start_month' => $wet ? 5 : 11,
            'window_start_day' => 1,
            'window_end_month' => $wet ? 7 : 1,
            'window_end_day' => $wet ? 31 : 31,
            'source' => 'national-default-philrice-double-cropping',
        ];
    }
}
