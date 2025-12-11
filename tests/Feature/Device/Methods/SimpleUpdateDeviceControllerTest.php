<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use GuzzleHttp\Client;
use Mockery;

class SimpleUpdateDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->device = Device::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        // Mocking GuzzleHttp Client
        $client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $client);
        $this->startSession();
    }

    public function test_simple_update_invalid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.simple.update', $this->device), [
            'name' => 'ab', // Terlalu pendek
            'topic' => '', // Kosong
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'topic']);
    }

    public function test_simple_update_valid_input()
    {
        // Menggunakan CSRF token dalam header
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.simple.update', $this->device), [
            'name' => 'Updated Device',
            'topic' => 'updated/topic',
        ]);

        $response->assertStatus(302); // Redirect
        $this->assertDatabaseHas('devices', [
            'id' => $this->device->id,
            'user_id' => $this->user->id,
            'name' => 'Updated Device',
            'topic' => 'updated/topic',
        ]);
    }

    public function test_simple_update_keep_same_topic()
    {
        // Test update dengan topik yang sama tidak akan error unique
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->putJson(route('devices.simple.update', $this->device), [
            'name' => 'Updated Device',
            'topic' => $this->device->topic, // Topik yang sama
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('devices', [
            'id' => $this->device->id,
            'topic' => $this->device->topic,
        ]);
    }

    // public function test_simple_update_duplicate_topic()
    // {
    //     // Membuat perangkat lain dengan topik yang sudah ada
    //     $otherDevice = Device::factory()->create(['topic' => 'existing/topic']);

    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.simple.update', $this->device), [
    //         'name' => 'Updated Device',
    //         'topic' => 'existing/topic', // Topik dari device lain
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['topic']);
    // }

    // public function test_simple_update_invalid_name_length()
    // {
    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.simple.update', $this->device), [
    //         'name' => 'This is a very long device name that exceeds the maximum length of 30 characters',
    //         'topic' => 'test/topic',
    //     ]);

    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors(['name']);
    // }

    // public function test_simple_update_same_name_and_topic()
    // {
    //     // Memastikan capacity tidak berubah
    //     $originalCapacity = $this->device->capacity;

    //     $response = $this->withHeaders([
    //         'X-CSRF-TOKEN' => csrf_token(),
    //     ])->putJson(route('devices.simple.update', $this->device), [
    //         'name' => 'Updated Device',
    //         'topic' => 'updated/topic',
    //     ]);

    //     $response->assertStatus(302);
        
    //     // Refresh device dari database
    //     $this->device->refresh();
    //     $this->assertEquals($originalCapacity, $this->device->capacity);
    //     $this->assertEquals('Updated Device', $this->device->name);
    //     $this->assertEquals('updated/topic', $this->device->topic);
    // }
}
