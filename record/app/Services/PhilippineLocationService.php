<?php

namespace App\Services;

use RuntimeException;

class PhilippineLocationService
{
    /**
     * @var array{
     *     regions:array<string, array<string, string>>,
     *     provinces:array<string, array<string, string>>,
     *     cities:array<string, array<string, string|bool>>,
     *     barangays:array<string, array<string, string>>
     * }|null
     */
    private static ?array $locationsByCode = null;

    /**
     * @return array{region:string,province:string,city:string,barangay:string}|null
     */
    public function resolveAddressCodes(
        string $regionCode,
        string $provinceCode,
        string $cityCode,
        string $barangayCode,
    ): ?array {
        $locations = $this->locationsByCode();
        $region = $locations['regions'][$regionCode] ?? null;
        $province = $locations['provinces'][$provinceCode] ?? null;
        $city = $locations['cities'][$cityCode] ?? null;
        $barangay = $locations['barangays'][$barangayCode] ?? null;

        if ($region === null || $province === null || $city === null || $barangay === null
            || $province['region'] !== $regionCode
            || $city['province'] !== $provinceCode
            || $barangay['cityMunCode'] !== $cityCode) {
            return null;
        }

        return [
            'region' => $region['designation'].' ('.$region['name'].')',
            'province' => $province['name'],
            'city' => $city['name'],
            'barangay' => $barangay['name'],
        ];
    }

    /**
     * @return array{
     *     regions:array<string, array<string, string>>,
     *     provinces:array<string, array<string, string>>,
     *     cities:array<string, array<string, string|bool>>,
     *     barangays:array<string, array<string, string>>
     * }
     */
    private function locationsByCode(): array
    {
        if (self::$locationsByCode !== null) {
            return self::$locationsByCode;
        }

        $path = public_path('data/philippine-geography-2024.json');
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the bundled Philippine geographic dataset.');
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        foreach (['regions', 'provinces', 'cities', 'barangays'] as $requiredCollection) {
            if (! isset($data[$requiredCollection]) || ! is_array($data[$requiredCollection])) {
                throw new RuntimeException('The bundled Philippine geographic dataset is invalid.');
            }
        }

        self::$locationsByCode = [
            'regions' => array_column($data['regions'], null, 'code'),
            'provinces' => array_column($data['provinces'], null, 'code'),
            'cities' => array_column($data['cities'], null, 'code'),
            'barangays' => array_column($data['barangays'], null, 'code'),
        ];

        return self::$locationsByCode;
    }
}
