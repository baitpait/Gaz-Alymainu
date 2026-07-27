<?php

namespace App\Services;

use App\Models\DriverLocation;
use App\Models\User;
use RuntimeException;

/**
 * Business Purpose: استقبال مواقع السائقين أثناء الوردية وتجميعها لخريطة الإدارة.
 */
class DriverLocationService
{
    /** أقصى عمر للنقطة (ثوانٍ) لاعتبارها «حية» على الخريطة. */
    public const FRESH_SECONDS = 180;

    /**
     * يبدأ مشاركة الموقع للسائق (وردية مفتوحة).
     */
    public function startSharing(int $driverUserId): DriverLocation
    {
        $this->assertDriver($driverUserId);

        return DriverLocation::query()->updateOrCreate(
            ['driver_user_id' => $driverUserId],
            ['is_sharing' => true]
        );
    }

    /**
     * يوقف مشاركة الموقع — يبقى آخر موقع محفوظاً لكن غير «حي».
     */
    public function stopSharing(int $driverUserId): DriverLocation
    {
        $this->assertDriver($driverUserId);

        return DriverLocation::query()->updateOrCreate(
            ['driver_user_id' => $driverUserId],
            ['is_sharing' => false]
        );
    }

    /**
     * يحدّث إحداثيات السائق أثناء الوردية المفتوحة.
     */
    public function updatePosition(
        int $driverUserId,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
    ): DriverLocation {
        $this->assertDriver($driverUserId);

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new RuntimeException('إحداثيات غير صالحة.');
        }

        $row = DriverLocation::query()->updateOrCreate(
            ['driver_user_id' => $driverUserId],
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'recorded_at' => now(),
                'is_sharing' => true,
            ]
        );

        return $row->fresh();
    }

    public function isSharing(int $driverUserId): bool
    {
        return (bool) DriverLocation::query()
            ->where('driver_user_id', $driverUserId)
            ->value('is_sharing');
    }

    public function latestFor(int $driverUserId): ?DriverLocation
    {
        return DriverLocation::query()
            ->where('driver_user_id', $driverUserId)
            ->first();
    }

    /**
     * علامات الخريطة للإدارة: السائقون المشاركون الذين لديهم إحداثيات.
     *
     * @return list<array{id: int, name: string, vehicle: string|null, lat: float, lng: float, accuracy: float|null, recorded_at: string|null, is_fresh: bool}>
     */
    public function mapMarkers(): array
    {
        return DriverLocation::query()
            ->with(['driver.assignedVehicle'])
            ->where('is_sharing', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (DriverLocation $loc): array {
                $driver = $loc->driver;

                return [
                    'id' => (int) $loc->driver_user_id,
                    'name' => $driver?->full_name ?? 'سائق #'.$loc->driver_user_id,
                    'vehicle' => $driver?->assignedVehicle?->name,
                    'lat' => (float) $loc->latitude,
                    'lng' => (float) $loc->longitude,
                    'accuracy' => $loc->accuracy !== null ? (float) $loc->accuracy : null,
                    'recorded_at' => $loc->recorded_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    'is_fresh' => $loc->isFresh(self::FRESH_SECONDS),
                    'status_label' => $loc->isFresh(self::FRESH_SECONDS)
                        ? 'حيّ'
                        : ($loc->is_sharing ? 'قديم (الشاشة مغلقة أو ضعيفة)' : 'غير متصل'),
                ];
            })
            ->values()
            ->all();
    }

    private function assertDriver(int $driverUserId): void
    {
        $user = User::query()->find($driverUserId);
        if ($user === null || ! $user->isDriver() || ! $user->is_active) {
            throw new RuntimeException('حساب السائق غير صالح.');
        }
    }
}
