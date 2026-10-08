<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    /**
     * @return array{location: string, temperature: float|int, condition: string, humidity: int, wind_speed: float|int}|null
     */
    public function current(): ?array
    {
        try {
            $weather = Http::connectTimeout(3)
                ->timeout(config('services.weather.timeout'))
                ->get(config('services.weather.forecast_url'), [
                    'latitude' => config('services.weather.latitude'),
                    'longitude' => config('services.weather.longitude'),
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                    'wind_speed_unit' => 'kmh',
                    'timezone' => 'auto',
                ])
                ->throw()
                ->json();

            $current = $weather['current'] ?? null;

            if (! is_array($weather) || ! is_array($current) || ! isset(
                $current['temperature_2m'],
                $current['relative_humidity_2m'],
                $current['weather_code'],
                $current['wind_speed_10m']
            )) {
                return null;
            }

            return [
                'location' => config('services.weather.city'),
                'temperature' => $current['temperature_2m'],
                'condition' => $this->descriptionForCode((int) $current['weather_code']),
                'humidity' => (int) $current['relative_humidity_2m'],
                'wind_speed' => $current['wind_speed_10m'],
            ];
        } catch (Throwable $exception) {
            Log::warning('Weather service unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function descriptionForCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            in_array($code, [1, 2, 3], true) => 'Partly cloudy',
            in_array($code, [45, 48], true) => 'Foggy',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'Rain',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'Snow',
            in_array($code, [95, 96, 99], true) => 'Thunderstorm',
            default => 'Unavailable',
        };
    }
}
