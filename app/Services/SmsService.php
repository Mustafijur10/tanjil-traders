<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Text messages to customers through a Bangladeshi SMS gateway.
 *   bulksmsbd - BulkSMSBD (api key + approved sender id)
 *   custom    - any gateway with a GET link; {phone}, {message}, {key} and {sender} are filled in
 * Every message is logged in sms_logs, sent or failed. A failed SMS never stops an order.
 */
class SmsService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('sms.enabled') && filled(Setting::get('sms.api_key') ?: Setting::get('sms.api_url'));
    }

    /** 01712345678 / +8801712345678 -> 8801712345678 */
    public static function normalize(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $local = substr($digits, -11);

        return preg_match('/^01[3-9]\d{8}$/', $local) ? '88' . $local : null;
    }

    public function send(string $phone, string $message, ?string $event = null, ?int $orderId = null): bool
    {
        $to = self::normalize($phone);
        $message = mb_substr(trim($message), 0, 600);
        [$ok, $response] = $to ? $this->deliver($to, $message) : [false, 'Not a valid Bangladeshi mobile number.'];

        DB::table('sms_logs')->insert([
            'phone'      => $to ?? mb_substr($phone, 0, 20),
            'message'    => $message,
            'event'      => $event,
            'order_id'   => $orderId,
            'status'     => $ok ? 'sent' : 'failed',
            'response'   => mb_substr((string) $response, 0, 500),
            'created_at' => now(),
        ]);

        return $ok;
    }

    private function deliver(string $to, string $message): array
    {
        $key = (string) Setting::get('sms.api_key');
        $sender = (string) Setting::get('sms.sender_id');

        try {
            if (Setting::get('sms.provider', 'bulksmsbd') === 'custom') {
                $url = strtr((string) Setting::get('sms.api_url'), [
                    '{phone}' => rawurlencode($to), '{message}' => rawurlencode($message), '{key}' => rawurlencode($key), '{sender}' => rawurlencode($sender),
                ]);
                $res = Http::timeout(15)->get($url);

                return [$res->successful(), $res->status() . ' ' . $res->body()];
            }

            $res = Http::timeout(15)->asForm()->post('https://bulksmsbd.net/api/smsapi', [
                'api_key' => $key, 'type' => 'text', 'number' => $to, 'senderid' => $sender, 'message' => $message,
            ]);
            $code = (int) $res->json('response_code');

            return [$res->successful() && $code === 202, $res->body()];
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }
}
