<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Translation;

/**
 * Everything every page needs once on load: public settings and the Bangla dictionary.
 */
class BootstrapController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'settings'     => Setting::publicValues(),
            'translations' => ['bn' => (object) Translation::dictionary()],
        ]);
    }
}
