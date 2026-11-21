<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returns) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in([...OrderReturn::STATUSES, 'all', 'open'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $status = $request->status ?: 'open';
        $base = OrderReturn::query()
            ->when($request->search, fn ($q, $s) => $q->whereHas('order', fn ($o) => $o
                ->where('order_number', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));

        $items = (clone $base)
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['requested', 'approved', 'received']))
            ->when(! in_array($status, ['all', 'open'], true), fn ($q) => $q->where('status', $status))
            ->with(['order:id,order_number,name,phone,total,payment_method,payment_status,status', 'items.orderItem:id,name,variant_label,price,qty'])
            ->latest()
            ->paginate(20);

        $counts = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return response()->json([
            'items'  => $items,
            'counts' => collect(OrderReturn::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])
                + ['all' => (int) $counts->sum(), 'open' => (int) (($counts['requested'] ?? 0) + ($counts['approved'] ?? 0) + ($counts['received'] ?? 0))],
            'next'   => ReturnService::NEXT,
        ]);
    }

    /** Staff records a return for a customer (e.g. brought back to the shop). */
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'reason'                => ['required', Rule::in(OrderReturn::REASONS)],
            'details'               => ['nullable', 'string', 'max:1000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.qty'           => ['required', 'integer', 'min:0'],
        ]);

        $return = $this->returns->create($order->load('items'), $data['items'], $data['reason'], $data['details'] ?? null, $request->user());

        return response()->json($return->load('items'), 201);
    }

    public function update(Request $request, OrderReturn $return)
    {
        $data = $request->validate([
            'status'        => ['required', Rule::in(OrderReturn::STATUSES)],
            'refund_amount' => ['nullable', 'required_if:status,refunded', 'numeric', 'min:0'],
            'refund_method' => ['nullable', 'required_if:status,refunded', Rule::in(OrderReturn::REFUND_METHODS)],
            'restock'       => ['sometimes', 'boolean'],
            'admin_note'    => ['nullable', 'string', 'max:1000'],
        ]);

        $this->returns->changeStatus($return, $data['status'], $data, $request->user());

        return $return->fresh(['order', 'items.orderItem']);
    }
}
