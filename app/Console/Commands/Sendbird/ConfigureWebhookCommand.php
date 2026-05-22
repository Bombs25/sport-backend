<?php

namespace App\Console\Commands\Sendbird;

use App\Services\Sendbird\SendbirdService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Configure le webhook Sendbird via la Platform API (Master token), sans passer
 * par le dashboard. Utile quand l'UID Webhooks du dashboard est inaccessible.
 *
 * Exemple :
 *   php artisan sendbird:webhook https://xxx.trycloudflare.com/api/v1/sendbird/webhook
 */
class ConfigureWebhookCommand extends Command
{
    /** Événement Sendbird qui déclenche un push de message O'Sport. */
    private const MESSAGE_SEND_EVENT = 'group_channel:message_send';

    protected $signature = 'sendbird:webhook
                            {url? : URL publique du webhook (https). Omettre pour afficher la config actuelle.}';

    protected $description = 'Active et configure le webhook Sendbird via la Platform API.';

    public function handle(SendbirdService $sendbird): int
    {
        $url = $this->argument('url');

        try {
            if ($url === null) {
                $config = $sendbird->webhookConfiguration();
                $this->info('Configuration webhook Sendbird actuelle :');
                $this->line(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }

            if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
                $this->error('L\'URL doit commencer par https:// (ou http:// en local).');

                return self::FAILURE;
            }

            $result = $sendbird->configureWebhook($url, [self::MESSAGE_SEND_EVENT]);

            $this->info('Webhook Sendbird configuré ✔');
            $this->line('URL      : '.$url);
            $this->line('Événement: '.self::MESSAGE_SEND_EVENT);
            $this->line('Réponse  : '.json_encode($result, JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Échec : '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
