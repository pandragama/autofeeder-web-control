<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use Mockery;

class SimpleStoreDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Mocking GuzzleHttp Client
        $client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $client);
        $this->startSession();
    }

    public function test_simple_store_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices', [
            'name' => 'ab', // Terlalu pendek (minimal 3 karakter)
            'topic' => '', // Kosong
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['name', 'topic']);
    }

    public function test_simple_store_valid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices', [
            'name' => 'Device Simple',
            'topic' => 'simple/topic',
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('devices', [
            'user_id' => $this->user->id,
            'name' => 'Device Simple',
            'topic' => 'simple/topic',
            'capacity' => 12, // Default capacity untuk simple store
        ]);
    }

    public function test_simple_store_duplicate_topic()
    {
        // Membuat perangkat dengan topik yang sudah ada
        $existingDevice = Device::factory()->create(['topic' => 'existing/topic']);

        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/devices', [
            'name' => 'Another Device',
            'topic' => 'existing/topic', // Topik yang sudah ada
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);
    }

    // public function test_simple_store_invalid_name_length()
    // {
    //     // Menggunakan CSRF token dalam header
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->postJson('/devices', [
    //         'name' => 'This is a very long device name that exceeds the maximum length of 30 characters',
    //         'topic' => 'test/topic',
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['name']);
    // }
}
