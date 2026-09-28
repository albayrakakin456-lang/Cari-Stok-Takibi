<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'user:grant-admin {email : Yönetici yapılacak kullanıcının e-posta adresi}';

    protected $description = 'Kayıtlı bir kullanıcıya yönetici yetkisi verir.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("{$email} adresine sahip kullanıcı bulunamadı.");

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$email} zaten yönetici.");

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info("{$email} kullanıcısına yönetici yetkisi verildi.");

        return self::SUCCESS;
    }
}
