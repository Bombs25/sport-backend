<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : gère le JSON `notification_preferences` du profil utilisateur.
 *  - `getForUser` : lit le JSON et le merge avec les défauts.
 *  - `update` : merge un patch partiel et persiste.
 *  - `shouldSend` : décide si on doit envoyer une notif d'un `category.key` sur
 *    un `channel` donné (utilisé par les jobs avant émission).
 *
 * Pourquoi : centraliser la logique de préférences pour ne pas la disperser
 * dans chaque `*NotificationJob`. Les défauts sont explicites et "opt-out"
 * (tout actif sauf media reçues, qui est opt-in).
 */
class NotificationPreferencesService
{
    /**
     * Défauts appliqués quand la colonne est NULL ou qu'une clé manque.
     *
     * @return array<string, array<string, bool>>
     */
    public function defaults(): array
    {
        return [
            'channels' => [
                'push' => true,
                'email' => true,
                'sms' => false,
            ],
            'social' => [
                'mentions' => true,
                'likes' => true,
                'comments' => true,
                'follow' => true,
            ],
            'teams' => [
                'ranking' => true,
                'trophies' => true,
                'member_changes' => true,
            ],
            'matches' => [
                'requests' => true,
                'reminders' => true,
                'score' => true,
                'end' => true,
            ],
            'messaging' => [
                'direct' => true,
                'media' => false,
            ],
        ];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getForUser(int $userId): array
    {
        $row = DB::table('user_profiles')
            ->where('user_id', $userId)
            ->value('notification_preferences');

        $stored = $row !== null ? (array) json_decode($row, true) : [];

        return $this->mergeWithDefaults($stored);
    }

    /**
     * @param  array<string, array<string, bool>>  $patch  Sections à mettre à jour (partiel par section).
     */
    public function update(int $userId, array $patch): void
    {
        $current = $this->getForUser($userId);

        foreach ($patch as $section => $values) {
            if (! isset($current[$section]) || ! is_array($values)) {
                continue;
            }
            foreach ($values as $key => $bool) {
                if (array_key_exists($key, $current[$section])) {
                    $current[$section][$key] = (bool) $bool;
                }
            }
        }

        DB::table('user_profiles')
            ->where('user_id', $userId)
            ->update([
                'notification_preferences' => json_encode($current),
                'updated_at' => now(),
            ]);
    }

    /**
     * Vérifie si on doit envoyer une notif `[$section.$key]` sur le canal `$channel`
     * pour `$userId`. Les deux flags doivent être `true` (activité + canal).
     */
    public function shouldSend(int $userId, string $section, string $key, string $channel = 'push'): bool
    {
        $prefs = $this->getForUser($userId);

        $activityOn = $prefs[$section][$key] ?? true;
        $channelOn = $prefs['channels'][$channel] ?? true;

        return $activityOn && $channelOn;
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, array<string, bool>>
     */
    private function mergeWithDefaults(array $stored): array
    {
        $defaults = $this->defaults();
        foreach ($defaults as $section => $values) {
            if (isset($stored[$section]) && is_array($stored[$section])) {
                foreach ($values as $k => $default) {
                    $defaults[$section][$k] = isset($stored[$section][$k]) ? (bool) $stored[$section][$k] : $default;
                }
            }
        }

        return $defaults;
    }
}
