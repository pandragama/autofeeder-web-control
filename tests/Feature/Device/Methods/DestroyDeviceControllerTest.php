<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Mockery;

class DestroyDeviceControllerTest extends TestCase
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

    public function test_destroy_successful()
    {
        $device = Device::factory()->create(['user_id' => $this->user->id]);
        $schedule = Schedule::factory()->create([
            'device_id' => $device->id,
            'time' => '12:00',
            'grams_per_feeding' => 60,
            'active' => 1,
        ]);

        // // Debugging: Check session data
        // $sessionData = session()->all();
        // Log::info('Session Data anjas:', $sessionData);

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->deleteJson('/devices/' . $device->id .'/delete');

        $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
        $this->assertDatabaseMissing($device); // Memastikan data tidak ada di database
        $this->assertDatabaseMissing($schedule); // Memastikan data tidak ada di database
    }

    // public function test_destroy_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     $device = Device::factory()->create(['user_id' => $this->user->id]);

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->deleteJson('/devices/' . $device->id .'/delete');

    //     $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
    //     $this->assertDatabaseMissing($device); // Memastikan data tidak ada di database

    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Error Communicating with Server");
    // }
}
