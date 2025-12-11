<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class StoreDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
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

        // Mocking GuzzleHttp Client
        $this->client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $this->client);
        $this->startSession(); // Ensure the session is started
    }

    public function test_store_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices/admin', [
            'user_id' => 99,
            'name' => 'ab', // Terlalu pendek (minimal 3 karakter)
            'topic' => '',
            'capacity' => 1, // Terlalu rendah (minimal 2)
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['user_id', 'name', 'topic', 'capacity']);
    }

    public function test_store_valid_input()
    {
        // Membuat pengguna untuk device
        $deviceUser = User::factory()->create();

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices/admin', [
            'user_id' => $deviceUser->id,
            'name' => 'Device Test',
            'topic' => 'test/topic',
            'capacity' => 5,
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('devices', [
            'user_id' => $deviceUser->id,
            'name' => 'Device Test',
            'topic' => 'test/topic',
            'capacity' => 5,
        ]);
    }

    public function test_store_duplicate_topic()
    {
        // Membuat perangkat dengan topik yang sudah ada
        $existingDevice = Device::factory()->create(['topic' => 'existing/topic']);
        
        $deviceUser = User::factory()->create();

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices/admin', [
            'user_id' => $deviceUser->id,
            'name' => 'Another Device',
            'topic' => 'existing/topic', // Topik yang sudah ada
            'capacity' => 5,
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['topic']);
    }

    // public function test_store_invalid_capacity()
    // {
    //     $deviceUser = User::factory()->create();

    //     // Kapasitas terlalu tinggi (maksimal 12)
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/devices/admin', [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'Device Test',
    //         'topic' => 'test/topic',
    //         'capacity' => 15,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['capacity']);
    // }

    // public function test_store_invalid_name_length()
    // {
    //     $deviceUser = User::factory()->create();

    //     // Nama terlalu panjang (maksimal 30)
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/devices/admin', [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'This is a very long device name that exceeds the maximum length',
    //         'topic' => 'test/topic',
    //         'capacity' => 5,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['name']);
    // }

    // public function test_store_nonexistent_user()
    // {
    //     // Menggunakan user_id yang tidak ada di database
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/devices/admin', [
    //         'user_id' => 9999,
    //         'name' => 'Device Test',
    //         'topic' => 'test/topic',
    //         'capacity' => 5,
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['user_id']);
    // }

    // public function test_store_valid_input_refresh_failed()
    // {
    //     $this->client->shouldReceive('request')
    //         ->once()
    //         ->with('POST', 'http://localhost:3000/api/refresh')
    //         ->andThrow(new RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test')));

    //     // Membuat pengguna untuk device
    //     $deviceUser = User::factory()->create();

    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/devices/admin', [
    //         'user_id' => $deviceUser->id,
    //         'name' => 'Device Test',
    //         'topic' => 'test/topic',
    //         'capacity' => 5,
    //     ]);

    //     $response->assertStatus(302); // Resource yang di-request telah dipindahkan sementara ke lokasi baru (permintaan HTTP berhasil)
    //     $response->assertSessionHas('toast_error', "Gagal menyegarkan jadwal di server: Error Communicating with Server");
    // }
}
