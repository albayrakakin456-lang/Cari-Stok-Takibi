<?php

namespace App\Services;

use RuntimeException;

class WebhookUrlGuard
{
    /** Production'da özel ağlara webhook gönderilmesini engeller. */
    public function assertSafe(string $url): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Güvensiz webhook URL engellendi.');
        }

        $addresses = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                }
                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if ($addresses === []) {
            throw new RuntimeException('Webhook adresinin sunucusu çözümlenemedi.');
        }

        foreach ($addresses as $address) {
            $publicAddress = filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($publicAddress === false) {
                throw new RuntimeException('Özel veya rezerve ağ adresine webhook gönderilemez.');
            }
        }
    }
}
