<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

/**
 * Two small lists that drive delivery: couriers (who carries the parcel, with a tracking link)
 * and shipping methods (what the customer pays for delivery at checkout).
 */
class DeliveryController extends Controller
{
    // ── Couriers ──
    public function couriers()
    {
        return Courier::orderBy('sort_order')->orderBy('id')->get()->map(fn ($c) => $c->toArray() + [
            'orders'  => Order::where('courier_id', $c->id)->count(),
            'transit' => Order::where('courier_id', $c->id)->where('status', 'shipped')->count(),
        ]);
    }

    public function storeCourier(Request $request)
    {
        $courier = Courier::create($this->courierData($request) + ['sort_order' => (int) Courier::max('sort_order') + 1]);

        return response()->json($courier, 201);
    }

    public function updateCourier(Request $request, Courier $courier)
    {
        $courier->update($this->courierData($request));

        return $courier;
    }

    public function destroyCourier(Courier $courier)
    {
        $courier->delete(); // orders keep working; their courier is cleared

        return response()->noContent();
    }

    // ── Shipping methods ──
    public function shipping()
    {
        return ShippingMethod::orderBy('sort_order')->orderBy('id')->get();
    }

    public function storeShipping(Request $request)
    {
        $method = ShippingMethod::create($this->shippingData($request) + ['sort_order' => (int) ShippingMethod::max('sort_order') + 1]);

        return response()->json($method, 201);
    }

    public function updateShipping(Request $request, ShippingMethod $method)
    {
        $method->update($this->shippingData($request));

        return $method;
    }

    public function destroyShipping(ShippingMethod $method)
    {
        $method->delete();

        return response()->noContent();
    }

    /** Body: { type: "couriers" | "shipping", ids: [3, 1, 2] } */
    public function reorder(Request $request)
    {
        $data = $request->validate(['type' => ['required', 'in:couriers,shipping'], 'ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $model = $data['type'] === 'couriers' ? Courier::class : ShippingMethod::class;
        foreach ($data['ids'] as $i => $id) {
            $model::whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return response()->noContent();
    }

    private function courierData(Request $request): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:80'],
            'phone'        => ['nullable', 'string', 'max:20'],
            'tracking_url' => ['nullable', 'url:https,http', 'max:255'],
            'is_active'    => ['boolean'],
        ]);
    }

    private function shippingData(Request $request): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'delivery_time' => ['nullable', 'string', 'max:60'],
            'charge'        => ['required', 'numeric', 'min:0'],
            'free_over'     => ['nullable', 'numeric', 'min:0'],
            'is_active'     => ['boolean'],
        ]);
    }
}
