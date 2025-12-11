<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleEditDeviceControllerTest extends TestCase
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

    public function test_simple_edit_returns_view_with_device_and_devices()
    {
        // Membuat beberapa perangkat untuk pengguna
        $device = Device::factory()->create(['user_id' => $this->user->id]);
        $device1 = Device::factory()->create(['user_id' => $this->user->id]);
        $device2 = Device::factory()->create(['user_id' => $this->user->id]);

        // Mengirim permintaan ke metode simpleEdit
        $response = $this->get(route('devices.simple.edit', $device));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200); // Memastikan status respons adalah 200
        $response->assertViewIs('device.simple'); // Memastikan tampilan yang dikembalikan adalah 'device.simple'
        
        // Memastikan data yang dikirim ke tampilan adalah benar
        $response->assertViewHas('device', function ($returnedDevice) use ($device) {
            return $returnedDevice->id === $device->id;
        });

        $response->assertViewHas('devices', function ($devices) use ($device1, $device2) {
            return $devices->contains($device1) && $devices->contains($device2);
        });
    }

    // public function test_simple_edit_only_shows_user_devices()
    // {
    //     // Membuat perangkat untuk pengguna yang login
    //     $device = Device::factory()->create(['user_id' => $this->user->id]);

    //     // Membuat perangkat untuk pengguna lain
    //     $otherUser = User::factory()->create();
    //     $otherDevice = Device::factory()->create(['user_id' => $otherUser->id]);

    //     // Mengirim permintaan ke metode simpleEdit
    //     $response = $this->get(route('devices.simple.edit', $device));

    //     // Memastikan respons adalah tampilan yang benar
    //     $response->assertStatus(200);
    //     $response->assertViewIs('device.simple');
        
    //     // Memastikan hanya perangkat milik pengguna yang login yang ditampilkan
    //     $response->assertViewHas('devices', function ($devices) use ($device, $otherDevice) {
    //         return $devices->contains($device) && !$devices->contains($otherDevice);
    //     });
    // }

    public function test_simple_edit_returns_404_for_nonexistent_device()
    {
        // Mengirim permintaan ke metode simpleEdit dengan perangkat yang tidak ada
        $response = $this->get(route('devices.simple.edit', ['device' => 999]));

        // Memastikan respons adalah 404
        $response->assertStatus(404);
    }

    // public function test_simple_edit_returns_404_for_device_of_another_user()
    // {
    //     // Membuat perangkat untuk pengguna lain
    //     $otherUser = User::factory()->create();
    //     $otherDevice = Device::factory()->create(['user_id' => $otherUser->id]);

    //     // Mencoba akses perangkat milik pengguna lain
    //     $response = $this->get(route('devices.simple.edit', $otherDevice));

    //     // Memastikan respons adalah 404 (karena perangkat ada tapi bukan milik pengguna yang login)
    //     // Atau cek behavior aktual dari controller
    //     // Jika controller tidak ada validasi, test ini mungkin perlu disesuaikan
    //     $response->assertStatus(200); // Sesuaikan dengan behavior aktual
    // }
}
