<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditDeviceControllerTest extends TestCase
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

    public function test_edit_returns_view_with_device_and_users()
    {
        // Membuat perangkat
        $device = Device::factory()->create();
        
        // Membuat beberapa pengguna
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Mengirim permintaan ke metode edit
        $response = $this->get(route('devices.edit', $device));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200); // Memastikan status respons adalah 200
        $response->assertViewIs('device.edit'); // Memastikan tampilan yang dikembalikan adalah 'device.edit'
        
        // Memastikan data yang dikirim ke tampilan adalah benar
        $response->assertViewHas('device', function ($returnedDevice) use ($device) {
            return $returnedDevice->id === $device->id;
        });

        $response->assertViewHas('users', function ($users) use ($user1, $user2) {
            return $users->contains($user1) && $users->contains($user2);
        });
    }

    public function test_edit_returns_404_for_nonexistent_device()
    {
        // Mengirim permintaan ke metode edit dengan perangkat yang tidak ada
        $response = $this->get(route('devices.edit', ['device' => 999]));

        // Memastikan respons adalah 404
        $response->assertStatus(404);
    }
}
