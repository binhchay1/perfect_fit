<?php

namespace App\Services;

use App\Enums\BodyMeasurement as BodyMeasurementEnum;
use App\Models\Product;
use App\Models\User;
use App\Models\UserBodyMeasurement;
use App\Repositories\UserBodyMeasurementRepository;

final class PerfectFitService
{
    public function __construct(
        private readonly UserBodyMeasurementRepository $measurementRepository
    ) {}

    public function getUserMeasurements(User $user): ?UserBodyMeasurement
    {
        return $this->measurementRepository->getUserMeasurements($user);
    }

    public function saveMeasurements(User $user, array $data): UserBodyMeasurement
    {
        $data['user_id'] = $user->id;
        return $this->measurementRepository->createOrUpdate($user, $data);
    }

    public function deleteMeasurements(User $user): bool
    {
        return $this->measurementRepository->deleteMeasurements($user);
    }

    public function recommendSize(User $user, Product $product): array
    {
        $measurements = $this->getUserMeasurements($user);

        if (!$measurements) {
            return [
                'has_measurements' => false,
                'recommended_size' => null,
                'message' => 'Please add your body measurements first',
            ];
        }

        $recommendedSize = $this->calculateBestFitSize($measurements, $product);

        return [
            'has_measurements' => true,
            'recommended_size' => $recommendedSize,
            'confidence' => $this->calculateConfidence($measurements, $recommendedSize),
            'measurements_used' => [
                'chest' => $measurements->chest,
                'waist' => $measurements->waist,
                'hips' => $measurements->hips,
            ],
            'fit_preference' => $measurements->preferred_fit ?? 'regular',
        ];
    }

    private function calculateBestFitSize(UserBodyMeasurement $measurements, Product $product): string
    {
        $sizeChart = BodyMeasurementEnum::SIZE_CHART;
        $bestSize = 'M';
        $minDiff = PHP_FLOAT_MAX;

        foreach ($sizeChart as $size => $ranges) {
            $chestDiff = abs($measurements->chest - (($ranges['chest'][0] + $ranges['chest'][1]) / 2));
            $waistDiff = abs($measurements->waist - (($ranges['waist'][0] + $ranges['waist'][1]) / 2));
            $hipsDiff = abs($measurements->hips - (($ranges['hips'][0] + $ranges['hips'][1]) / 2));

            $totalDiff = $chestDiff + $waistDiff + $hipsDiff;

            if ($totalDiff < $minDiff) {
                $minDiff = $totalDiff;
                $bestSize = $size;
            }
        }

        if ($measurements->preferred_fit === BodyMeasurementEnum::FIT_TYPE_TIGHT) {
            $bestSize = $this->adjustSizeDown($bestSize);
        } elseif ($measurements->preferred_fit === BodyMeasurementEnum::FIT_TYPE_LOOSE) {
            $bestSize = $this->adjustSizeUp($bestSize);
        }

        return $bestSize;
    }

    private function calculateConfidence(UserBodyMeasurement $measurements, string $size): string
    {
        return 'high';
    }

    private function adjustSizeDown(string $size): string
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL'];
        $index = array_search($size, $sizes);
        return $index > 0 ? $sizes[$index - 1] : $size;
    }

    private function adjustSizeUp(string $size): string
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL'];
        $index = array_search($size, $sizes);
        return $index < count($sizes) - 1 ? $sizes[$index + 1] : $size;
    }
}

