<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

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
    }

    public function test_index_returns_view_with_devices()
    {
        // Membuat beberapa perangkat untuk pengguna
        $device1 = Device::factory()->create();
        $device2 = Device::factory()->create();

        // Mengirim permintaan ke metode index
        $response = $this->get(route('devices.index'));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200); // Memastikan status respons adalah 200
        $response->assertViewIs('device.index'); // Memastikan tampilan yang dikembalikan adalah 'device.index'
        
        // Memastikan data yang dikirim ke tampilan adalah benar
        $response->assertViewHas('devices', function ($devices) use ($device1, $device2) {
            return $devices->contains($device1) && $devices->contains($device2);
        });
    }

    public function test_index_returns_empty_when_no_devices()
    {
        // Mengirim permintaan ke metode index tanpa membuat device
        $response = $this->get(route('devices.index'));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200);
        $response->assertViewIs('device.index');
        
        // Memastikan tidak ada perangkat yang dikirim ke tampilan
        $response->assertViewHas('devices', function ($devices) {
            return $devices->isEmpty();
        });
    }
}
