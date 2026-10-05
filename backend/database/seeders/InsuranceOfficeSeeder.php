<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Insurance\Models\InsuranceOffice;
use Illuminate\Database\Seeder;

final class InsuranceOfficeSeeder extends Seeder
{
    private const VERIFY_NOTE = 'Confirm address and contact via pcic.gov.ph or your Municipal Agriculturist Office.';

    /**
     * Addresses/phones only where corroborated by the PCIC Citizen's Charter.
     * All other regions seed as name-only rows — never fabricated contacts.
     *
     * @var list<array{region_code: ?string, name: string, city: ?string, address: ?string, phone: ?string}>
     */
    private const OFFICES = [
        [
            'region_code' => null,
            'name' => 'PCIC Head Office',
            'city' => 'Quezon City',
            'address' => '7/F NIA Building A, NIA Complex, EDSA, Diliman, Quezon City',
            'phone' => '(02) 8441-1323',
        ],
        [
            'region_code' => '010000000',
            'name' => 'PCIC Regional Office I',
            'city' => 'Urdaneta City',
            'address' => '3/F LBP Building, Urdaneta City, Pangasinan',
            'phone' => '(075) 632-3248',
        ],
        [
            'region_code' => '020000000',
            'name' => 'PCIC Regional Office II',
            'city' => 'Tuguegarao City',
            'address' => 'Regional Government Center, Carig, Tuguegarao City, Cagayan',
            'phone' => '(078) 304-2408',
        ],
        [
            'region_code' => '030000000',
            'name' => 'PCIC Regional Office III',
            'city' => null,
            'address' => null,
            'phone' => '(045) 8961-5717',
        ],
        [
            'region_code' => '040000000',
            'name' => 'PCIC Regional Office (CALABARZON)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '170000000',
            'name' => 'PCIC Regional Office (MIMAROPA)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '050000000',
            'name' => 'PCIC Regional Office (Bicol Region)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '060000000',
            'name' => 'PCIC Regional Office (Western Visayas)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '070000000',
            'name' => 'PCIC Regional Office VII',
            'city' => 'Cebu City',
            'address' => '2/F DBP Building, Osmeña Boulevard, Cebu City 6000',
            'phone' => '(032) 253-8686',
        ],
        [
            'region_code' => '080000000',
            'name' => 'PCIC Regional Office VIII',
            'city' => 'Tacloban City',
            'address' => 'F. Mendoza Realty, Niño Street, Tacloban City, Leyte',
            'phone' => '(053) 321-3013',
        ],
        [
            'region_code' => '090000000',
            'name' => 'PCIC Regional Office IX',
            'city' => 'Pagadian City',
            'address' => 'Regional Complex, Pajares Avenue, Pagadian City, Zamboanga del Sur',
            'phone' => '(062) 214-1737',
        ],
        [
            'region_code' => '100000000',
            'name' => 'PCIC Regional Office X',
            'city' => 'Cagayan de Oro City',
            'address' => '3/F One Montecarlo Building, Hayes-Corrales Street, Cagayan de Oro City, Misamis Oriental',
            'phone' => '(088) 857-2983',
        ],
        [
            'region_code' => '110000000',
            'name' => 'PCIC Regional Office (Davao Region)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '120000000',
            'name' => 'PCIC Regional Office (SOCCSKSARGEN)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '130000000',
            'name' => 'PCIC Head Office (serves NCR)',
            'city' => 'Quezon City',
            'address' => '7/F NIA Building A, NIA Complex, EDSA, Diliman, Quezon City',
            'phone' => '(02) 8441-1323',
        ],
        [
            'region_code' => '140000000',
            'name' => 'PCIC Regional Office (Cordillera Administrative Region)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '160000000',
            'name' => 'PCIC Regional Office (Caraga)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
        [
            'region_code' => '150000000',
            'name' => 'PCIC Regional Office (BARMM)',
            'city' => null,
            'address' => null,
            'phone' => null,
        ],
    ];

    public function run(): void
    {
        foreach (self::OFFICES as $office) {
            // NULL region_code never matches in a WHERE clause, so match head office by name.
            $match = $office['region_code'] === null
                ? ['name' => $office['name']]
                : ['region_code' => $office['region_code']];

            InsuranceOffice::updateOrCreate($match, [...$office, 'source_note' => self::VERIFY_NOTE]);
        }
    }
}
