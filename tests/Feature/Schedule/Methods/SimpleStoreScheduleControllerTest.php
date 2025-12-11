<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class SimpleStoreScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $device;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->device = Device::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        // Mocking GuzzleHttp Client
        $this->client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $this->client);
        $this->startSession(); // Ensure the session is started
    }

    public function test_simple_store_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/schedules', [
            'device_id' => 99,
            'time' => '',
            'grams_per_feeding' => 0,
            'active' => 1,
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['device_id', 'time', 'grams_per_feeding']);
    }

    public function test_simple_store_valid_input_no_days()
    {
        // // Mock API response success
        // $this->client->shouldReceive('request')
        //     ->once()
        //     ->with('POST', 'http://localhost:3000/api/refresh')
        //     ->andReturn($this->createMockResponse(200));

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'active' => 1,
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'days' => '-', // Tidak ada hari yang dipilih
        ]);
    }

    public function test_simple_store_valid_input_with_days()
    {
        // // Mock API response success
        // $this->client->shouldReceive('request')
        //     ->once()
        //     ->with('POST', 'http://localhost:3000/api/refresh')
        //     ->andReturn($this->createMockResponse(200));

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'active' => 1,
            'days_monday' => 'Monday',
            'days_wednesday' => 'Wednesday',
            'days_friday' => 'Friday',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'days' => 'Monday Wednesday Friday ', // Hari yang dipilih
        ]);
    }

    // public function test_simple_store_valid_input_all_days()
    // {
    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '08:00',
    //         'grams_per_feeding' => 100,
    //         'active' => 1,
    //         'days_monday' => 'Monday',
    //         'days_tuesday' => 'Tuesday',
    //         'days_wednesday' => 'Wednesday',
    //         'days_thursday' => 'Thursday',
    //         'days_friday' => 'Friday',
    //         'days_saturday' => 'Saturday',
    //         'days_sunday' => 'Sunday',
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '08:00',
    //         'grams_per_feeding' => 100,
    //         'days' => 'Monday Tuesday Wednesday Thursday Friday Saturday Sunday ', // Semua hari
    //     ]);
    // }

    // public function test_simple_store_grams_calculation()
    // {
    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '10:00',
    //         'grams_per_feeding' => 30,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302);
    //     // servo_seconds = (30 / 30) * 1000 = 1000
    //     $this->assertDatabaseHas('schedules', [
    //         'device_id' => $this->device->id,
    //         'grams_per_feeding' => 30,
    //         'servo_seconds' => 1000,
    //     ]);
    // }

    // public function test_simple_store_invalid_grams_too_low()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 20, // Terlalu rendah (minimal 30)
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['grams_per_feeding']);
    // }

    // public function test_simple_store_invalid_grams_too_high()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 1500, // Terlalu tinggi (maksimal 1000)
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['grams_per_feeding']);
    // }

    // public function test_simple_store_invalid_device()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => 9999, // Device tidak ada
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['device_id']);
    // }

    // public function test_simple_store_valid_input_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Connection failed", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //         'days_monday' => 'Monday',
    //         'days_wednesday' => 'Wednesday',
    //         'days_friday' => 'Friday',
    //     ]);

    //     $response->assertStatus(302);
    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Connection failed");
    // }

    // public function test_simple_store_creates_with_active_false()
    // {
    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Menggunakan CSRF token tanpa menyertakan active
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'device_id' => $this->device->id,
    //         'active' => 0, // Default is 0 (inactive)
    //     ]);
    // }

    // protected function createMockResponse($statusCode)
    // {
    //     $response = Mockery::mock();
    //     $response->shouldReceive('getStatusCode')->andReturn($statusCode);
    //     return $response;
    // }
}
