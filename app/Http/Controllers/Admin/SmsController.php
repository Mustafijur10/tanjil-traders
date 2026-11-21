<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsController extends Controller
{
    public function logs(Request $request)
    {
        $request->validate(['status' => ['nullable', 'in:sent,failed']]);

        return response()->json([
            'items'  => DB::table('sms_logs')->leftJoin('orders', 'orders.id', '=', 'sms_logs.order_id')
                ->when($request->status, fn ($q, $s) => $q->where('sms_logs.status', $s))
                ->select('sms_logs.*', 'orders.order_number')->orderByDesc('sms_logs.id')->paginate(25),
            'counts' => [
                'sent'   => DB::table('sms_logs')->where('status', 'sent')->where('created_at', '>=', now()->subDays(30))->count(),
                'failed' => DB::table('sms_logs')->where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    /** Send a test message with the saved gateway settings. */
    public function test(Request $request, SmsService $sms)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $ok = $sms->send($data['phone'], 'Test message from ' . \App\Models\Setting::get('store.name') . '. SMS is working.', 'test');
        $response = DB::table('sms_logs')->latest('id')->value('response');

        return response()->json([
            'sent'     => $ok,
            'response' => $response,
            'message'  => $ok ? 'Test SMS sent.' : 'The SMS was not sent. Gateway answer: ' . mb_substr((string) $response, 0, 200),
        ], $ok ? 200 : 422);
    }
}
