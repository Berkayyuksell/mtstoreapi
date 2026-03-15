<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mtstore.com'],
            [
                'name'       => 'MT Store Admin',
                'email'      => 'admin@mtstore.com',
                'password'   => Hash::make('Admin@123!'),
                'is_admin'   => true,
                'project_id' => null,
                'company_id' => null,
                'office_id'  => null,
                'store_id'   => null,
            ]
        );

        $this->command->info('Admin kullanıcı oluşturuldu:');
        $this->command->info('  E-posta : admin@mtstore.com');
        $this->command->info('  Şifre   : Admin@123!');
    }
}
