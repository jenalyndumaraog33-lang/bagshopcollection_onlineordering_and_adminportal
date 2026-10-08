<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_mapped_weather_data(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 30.5,
                    'relative_humidity_2m' => 72,
                    'weather_code' => 2,
                    'wind_speed_10m' => 12.4,
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Arakan, North Cotabato')
            ->assertSee('30.5')
            ->assertSee('Partly cloudy')
            ->assertSee('72%')
            ->assertSee('12.4 km/h');
    }

    public function test_dashboard_displays_fallback_when_weather_api_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.open-meteo.com/*' => Http::failedConnection(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Weather information is currently unavailable. Please try again later.');
    }
}
