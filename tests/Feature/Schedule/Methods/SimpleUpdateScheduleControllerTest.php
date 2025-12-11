<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class SimpleUpdateScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $device;
    protected $schedule;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->device = Device::factory()->create(['user_id' => $this->user->id]);
        $this->schedule = Schedule::factory()->create(['device_id' => $this->device->id]);
        $this->actingAs($this->user);

        // Mocking GuzzleHttp Client
        $this->client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $this->client);
        $this->startSession(); // Ensure the session is started
    }

    public function test_simple_update_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('schedules.simple.update', $this->schedule), [
            'device_id' => 99,
            'time' => '',
            'grams_per_feeding' => 0,
            'active' => 1,
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['device_id', 'time', 'grams_per_feeding']);
    }

    public function test_simple_update_valid_input_no_days()
    {
        // // Mock API response success
        // $this->client->shouldReceive('request')
        //     ->once()
        //     ->with('POST', 'http://localhost:3000/api/refresh')
        //     ->andReturn($this->createMockResponse(200));

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('schedules.simple.update', $this->schedule), [
            'device_id' => $this->device->id,
            'time' => '14:00',
            'grams_per_feeding' => 80,
            'active' => 1,
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('schedules', [
            'id' => $this->schedule->id,
            'device_id' => $this->device->id,
            'time' => '14:00',
            'grams_per_feeding' => 80,
            'days' => '-', // Tidak ada hari yang dipilih
        ]);
    }

    public function test_simple_update_valid_input_with_days()
    {
        // // Mock API response success
        // $this->client->shouldReceive('request')
        //     ->once()
        //     ->with('POST', 'http://localhost:3000/api/refresh')
        //     ->andReturn($this->createMockResponse(200));

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('schedules.simple.update', $this->schedule), [
            'device_id' => $this->device->id,
            'time' => '09:00',
            'grams_per_feeding' => 50,
            'active' => 0,
            'days_tuesday' => 'Tuesday',
            'days_thursday' => 'Thursday',
            'days_saturday' => 'Saturday',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('schedules', [
            'id' => $this->schedule->id,
            'device_id' => $this->device->id,
            'time' => '09:00',
            'grams_per_feeding' => 50,
            'active' => 0,
            'days' => 'Tuesday Thursday Saturday ', // Hari yang dipilih
        ]);
    }

    // public function test_simple_update_changes_from_days_to_no_days()
    // {
    //     // Buat schedule dengan days
    //     $schedule = Schedule::factory()->create([
    //         'device_id' => $this->device->id,
    //         'days' => 'Monday Wednesday Friday '
    //     ]);

    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Update tanpa days
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '15:00',
    //         'grams_per_feeding' => 70,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'id' => $schedule->id,
    //         'days' => '-', // Days berubah menjadi '-'
    //     ]);
    // }

    // public function test_simple_update_changes_from_no_days_to_days()
    // {
    //     // Buat schedule tanpa days
    //     $schedule = Schedule::factory()->create([
    //         'device_id' => $this->device->id,
    //         'days' => '-'
    //     ]);

    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Update dengan days
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '11:00',
    //         'grams_per_feeding' => 45,
    //         'active' => 1,
    //         'days_monday' => 'Monday',
    //         'days_wednesday' => 'Wednesday',
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'id' => $schedule->id,
    //         'days' => 'Monday Wednesday ', // Days berubah
    //     ]);
    // }

    // public function test_simple_update_grams_calculation()
    // {
    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '13:00',
    //         'grams_per_feeding' => 60, // 60 / 30 * 1000 = 2000
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'id' => $this->schedule->id,
    //         'grams_per_feeding' => 60,
    //         'servo_seconds' => 2000,
    //     ]);
    // }

    // public function test_simple_update_invalid_grams_too_low()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 15, // Terlalu rendah (minimal 30)
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['grams_per_feeding']);
    // }

    // public function test_simple_update_invalid_grams_too_high()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 2000, // Terlalu tinggi (maksimal 1000)
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['grams_per_feeding']);
    // }

    // public function test_simple_update_invalid_device()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => 9999, // Device tidak ada
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['device_id']);
    // }

    // public function test_simple_update_valid_input_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Connection timeout", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302);
    //     // Note: simpleUpdate doesn't seem to return error message in catch block based on the controller
    // }

    // public function test_simple_update_with_different_device()
    // {
    //     // Buat device lain untuk user
    //     $otherDevice = Device::factory()->create(['user_id' => $this->user->id]);

    //     // Mock API response success
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andReturn($this->createMockResponse(200));

    //     // Update dengan device yang berbeda
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('schedules.simple.update', $this->schedule), [
    //         'device_id' => $otherDevice->id,
    //         'time' => '16:00',
    //         'grams_per_feeding' => 90,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302);
    //     $this->assertDatabaseHas('schedules', [
    //         'id' => $this->schedule->id,
    //         'device_id' => $otherDevice->id,
    //         'time' => '16:00',
    //     ]);
    // }

    // protected function createMockResponse($statusCode)
    // {
    //     $response = Mockery::mock();
    //     $response->shouldReceive('getStatusCode')->andReturn($statusCode);
    //     return $response;
    // }
}
