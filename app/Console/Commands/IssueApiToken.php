<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueApiToken extends Command
{
    /**
     * Örnekler:
     * php artisan api:issue-token entegrasyon@example.com
     * php artisan api:issue-token entegrasyon@example.com --name=postman --read-only
     */
    protected $signature = 'api:issue-token
                            {email : Tokenın bağlanacağı kullanıcının e-posta adresi}
                            {--name=postman : Tokenı ayırt etmek için görünen ad}
                            {--read-only : Yalnızca api:read yetkisi ver}';

    protected $description = 'Bir kullanıcı için Postman veya dış servis Bearer tokenı üretir.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $tokenName = trim((string) $this->option('name'));

        if ($tokenName === '') {
            $this->error('Token adı boş olamaz.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("{$email} adresine sahip kullanıcı bulunamadı.");

            return self::FAILURE;
        }

        $abilities = $this->option('read-only')
            ? ['api:read']
            : ['api:read', 'api:write'];

        $plainTextToken = $user->createToken($tokenName, $abilities)->plainTextToken;

        $this->info("{$user->email} için token oluşturuldu.");
        $this->line('Yetkiler: '.implode(', ', $abilities));
        $this->warn('Bu token yalnızca şimdi gösterilir. Güvenli bir yerde saklayın:');
        $this->newLine();
        $this->line($plainTextToken);

        return self::SUCCESS;
    }
}
