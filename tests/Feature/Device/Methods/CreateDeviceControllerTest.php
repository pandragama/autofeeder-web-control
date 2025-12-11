<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDeviceControllerTest extends TestCase
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

    public function test_create_returns_view_with_users()
    {
        // Membuat beberapa pengguna untuk pengujian
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Mengirim permintaan ke metode create
        $response = $this->get(route('devices.create'));

        // Memastikan respons adalah tampilan yang benar
        $response->assertStatus(200); // Memastikan status respons adalah 200
        $response->assertViewIs('device.create'); // Memastikan tampilan yang dikembalikan adalah 'device.create'
        
        // Memastikan data yang dikirim ke tampilan adalah benar
        $response->assertViewHas('users', function ($users) use ($user1, $user2) {
            return $users->contains($user1) && $users->contains($user2);
        });
    }

    // public function test_create_returns_empty_users_when_no_users()
    // {
    //     // Mengirim permintaan ke metode create tanpa membuat pengguna tambahan
    //     $response = $this->get(route('devices.create'));

    //     // Memastikan respons adalah tampilan yang benar
    //     $response->assertStatus(200);
    //     $response->assertViewIs('device.create');
    // }
}
