<?php

namespace Tests\Feature\Api\V1\Sendbird;

use App\Jobs\SendbirdMessagePushJob;
use App\Models\User;
use App\Services\Notifications\ExpoPushService;
use App\Services\Sendbird\SendbirdMessagePushService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendbirdWebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const API_TOKEN = 'test-api-token';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sendbird.api_token', self::API_TOKEN);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function signedHeaders(array $payload): array
    {
        $body = json_encode($payload);

        return [
            'x-sendbird-signature' => hash_hmac('sha256', $body, self::API_TOKEN),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messageSendPayload(string $senderId, string $channelUrl): array
    {
        return [
            'category' => 'group_channel:message_send',
            'sender' => ['user_id' => $senderId, 'nickname' => 'Alice'],
            'channel' => ['channel_url' => $channelUrl],
            'payload' => ['message' => 'Salut !'],
            'members' => [
                ['user_id' => $senderId, 'is_online' => true],
                ['user_id' => 'osport_999', 'is_online' => false],
            ],
        ];
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $payload = $this->messageSendPayload('osport_1', 'chan_1');

        $this->postJson('/api/v1/sendbird/webhook', $payload, [
            'x-sendbird-signature' => 'wrong-signature',
        ])->assertUnauthorized();
    }

    public function test_message_send_event_dispatches_push_job(): void
    {
        Bus::fake();
        $payload = $this->messageSendPayload('osport_1', 'chan_1');

        $this->postJson('/api/v1/sendbird/webhook', $payload, $this->signedHeaders($payload))
            ->assertOk()
            ->assertJsonPath('received', true);

        Bus::assertDispatched(SendbirdMessagePushJob::class);
    }

    public function test_other_event_categories_are_ignored(): void
    {
        Bus::fake();
        $payload = [
            'category' => 'group_channel:create',
            'channel' => ['channel_url' => 'chan_1'],
        ];

        $this->postJson('/api/v1/sendbird/webhook', $payload, $this->signedHeaders($payload))
            ->assertOk();

        Bus::assertNotDispatched(SendbirdMessagePushJob::class);
    }

    public function test_push_service_notifies_offline_recipient_only(): void
    {
        Http::fake(['exp.host/*' => Http::response(['data' => []], 200)]);

        $sender = User::factory()->create(['fcm_token' => 'ExponentPushToken[sender]']);
        $recipient = User::factory()->create(['fcm_token' => 'ExponentPushToken[recipient]']);

        DB::table('sendbird_accounts')->insert([
            ['user_id' => $sender->id, 'sendbird_user_id' => 'osport_'.$sender->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $recipient->id, 'sendbird_user_id' => 'osport_'.$recipient->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $payload = [
            'category' => 'group_channel:message_send',
            'sender' => ['user_id' => 'osport_'.$sender->id, 'nickname' => 'Alice'],
            'channel' => ['channel_url' => 'chan_x'],
            'payload' => ['message' => 'Coucou'],
            'members' => [
                ['user_id' => 'osport_'.$sender->id, 'is_online' => true],
                ['user_id' => 'osport_'.$recipient->id, 'is_online' => false],
            ],
        ];

        app(SendbirdMessagePushService::class)->handleMessageSendWebhook($payload);

        // Un seul appel Expo, ciblant le destinataire hors ligne (pas l'expéditeur).
        Http::assertSent(function ($request) {
            $body = $request->data();
            $tokens = array_column($body, 'to');

            return in_array('ExponentPushToken[recipient]', $tokens, true)
                && ! in_array('ExponentPushToken[sender]', $tokens, true);
        });
        $this->assertInstanceOf(ExpoPushService::class, app(ExpoPushService::class));
    }
}
