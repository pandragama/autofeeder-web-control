<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Mockery;

class StoreScheduleControllerTest extends TestCase
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

    public function test_store_invalid_input()
    {
        // Debugging: Check session data
        // $sessionData = session()->all();
        // Log::info('Session Data store 1:', $sessionData);
        
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/schedules', [
            'device_id' => 99,
            'time' => '',
            'grams_per_feeding' => 0,
            'active' => 1,
        ]);

        $response->assertStatus(422); // Uprocessable Entity, tidak memenuhi kriteria validasi yang ditentukan oleh server
        $response->assertJsonValidationErrors(['device_id', 'time', 'grams_per_feeding']);
    }

    public function test_store_valid_input_no_days()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'active' => 1,
        ]);

        $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
        $this->assertDatabaseHas('schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'days' => '-', // Tidak ada hari yang dipilih
        ]);
    }

    public function test_store_valid_input_with_days()
    {
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
        ]);

        $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
        $this->assertDatabaseHas('schedules', [
            'device_id' => $this->device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'days' => 'Monday Wednesday ', // Hari yang dipilih
        ]);
    }

    // public function test_store_valid_input_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //         'days_monday' => 'Monday',
    //     ]);

    //     $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Error Communicating with Server");
    // }

    // public function test_store_valid_input_no_days_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/schedules', [
    //         'device_id' => $this->device->id,
    //         'time' => '12:00',
    //         'grams_per_feeding' => 60,
    //         'active' => 1,
    //     ]);

    //     $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Error Communicating with Server");
    // }
}