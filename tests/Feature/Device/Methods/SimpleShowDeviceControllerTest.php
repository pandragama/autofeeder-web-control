<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleShowDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Membuat pengguna untuk pengujian
        $this->user = User::factory()->create();
        $this->actingAs($this->user); // Mengautentikasi pengguna
    }

    public function test_simple_show_returns_view_with_devices()
    {
        // Membuat beberapa perangkat untuk pengguna
        $device1 = Device::factory()->create(['user_id' => $this->user->id]);
        $device2 = Device::factory()->create(['user_id' => $this->user->id]);

        // Membuat perangkat untuk pengguna lain (tidak boleh tampil)
        $otherUser = User::factory()->create();
        $otherDevice = Device::factory()->create(['user_id' => $otherUser->id]);

        // Mengirim permintaan ke metode simpleShow
        $response = $this->get(route('devices.simple'));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200); // Memastikan status respons adalah 200
        $response->assertViewIs('device.simple'); // Memastikan tampilan yang dikembalikan adalah 'device.simple'
        
        // Memastikan data yang dikirim ke tampilan adalah benar
        $response->assertViewHas('devices', function ($devices) use ($device1, $device2, $otherDevice) {
            // Hanya perangkat milik pengguna yang login
            return $devices->contains($device1) && 
                   $devices->contains($device2) &&
                   !$devices->contains($otherDevice);
        });
    }

    public function test_simple_show_returns_empty_when_no_devices()
    {
        // Mengirim permintaan ke metode simpleShow tanpa membuat perangkat
        $response = $this->get(route('devices.simple'));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200);
        $response->assertViewIs('device.simple');
        
        // Memastikan tidak ada perangkat yang dikirim ke tampilan
        $response->assertViewHas('devices', function ($devices) {
            return $devices->isEmpty();
        });
    }
}
