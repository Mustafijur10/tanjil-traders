<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::adminValues();
    }

    /** Body: { values: { "store.name": "...", ... } }. Unknown keys are ignored. */
    public function update(Request $request)
    {
        $data = $request->validate(['values' => ['required', 'array']]);
        Setting::put($data['values']);

        return Setting::adminValues();
    }
}
