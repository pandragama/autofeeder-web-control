<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class UpdateDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $device;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        // Membuat pengguna untuk pengujian
        // Periksa ketersediaan akun tester
        $tester = User::where('email', 'admin@finbites.com')->first();
        if ($tester == null) {
            // Membuat pengguna dengan email khusus tester
            $this->user = User::factory()->create([
                'name' => 'Tester',
                'email' => 'admin@finbites.com',
            ]); 
        } else {
            $this->user = $tester;
        }
        $this->actingAs($this->user); // Mengautentikasi pengguna

        $this->device = Device::factory()->create();

        // Mocking GuzzleHttp Client
        $this->client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $this->client);
        $this->startSession();
    }

    public function test_update_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.update', $this->device), [
            'user_id' => 99,
            'name' => 'ab', // Terlalu pendek
            'topic' => '',
            'capacity' => 1, // Terlalu rendah
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id', 'name', 'topic', 'capacity']);
    }

    public function test_update_valid_input()
    {
        $deviceUser = User::factory()->create();

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.update', $this->device), [
            'user_id' => $deviceUser->id,
            'name' => 'Updated Device',
            'topic' => 'updated/topic',
            'capacity' => 8,
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('devices', [
            'id' => $this->device->id,
            'user_id' => $deviceUser->id,
            'name' => 'Updated Device',
            'topic' => 'updated/topic',
            'capacity' => 8,
        ]);
    }

    public function test_update_keep_same_topic()
    {
        // Test update dengan topik yang sama tidak akan error unique
        $deviceUser = User::factory()->create();

        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.update', $this->device), [
            'user_id' => $deviceUser->id,
            'name' => 'Updated Device',
            'topic' => $this->device->topic, // Topik yang sama
            'capacity' => 8,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('devices', [
            'id' => $this->device->id,
            'topic' => $this->device->topic,
        ]);
    }

    // public function test_update_duplicate_topic_different_device()
    // {
    //     // Membuat perangkat lain dengan topik yang sudah ada
    //     $otherDevice = Device::factory()->create(['topic' => 'existing/topic']);
    //     $deviceUser = User::factory()->create();

    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.update', $this->device), [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'Updated Device',
    //         'topic' => 'existing/topic', // Topik dari device lain
    //         'capacity' => 8,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['topic']);
    // }

    // public function test_update_invalid_capacity()
    // {
    //     $deviceUser = User::factory()->create();

    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.update', $this->device), [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'Updated Device',
    //         'topic' => 'updated/topic',
    //         'capacity' => 15, // Terlalu tinggi
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['capacity']);
    // }

    // public function test_update_valid_input_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     $deviceUser = User::factory()->create();

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.update', $this->device), [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'Updated Device',
    //         'topic' => 'updated/topic',
    //         'capacity' => 8,
    //     ]);

    //     $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Error Communicating with Server");
    // }
}
