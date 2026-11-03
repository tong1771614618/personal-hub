<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 如果还没用户，创建一个默认 admin
        if (User::count() === 0) {
            User::create([
                'name' => 'admin',
                'email' => 'admin@local.dev',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]);
            $this->command->info('Default admin created: admin@local.dev / admin123');
            return;
        }

        // 否则把第一个用户提升为 admin
        $first = User::orderBy('id')->first();
        if ($first && $first->role !== 'admin') {
            $first->update(['role' => 'admin']);
            $this->command->info("Promoted user #{$first->id} to admin");
        }
    }
}
