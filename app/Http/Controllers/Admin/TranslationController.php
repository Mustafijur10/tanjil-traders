<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/**
 * Settings -> Language & Translations.
 * Admins see each English sentence and write, fix or approve its Bangla line.
 */
class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:' . implode(',', Translation::STATUSES)],
            'group'  => ['nullable', 'in:ui,content'],
        ]);

        $items = Translation::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('source', 'like', "%{$s}%")->orWhere('bn', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->group, fn ($q, $g) => $q->where('group', $g))
            ->orderByRaw("CASE status WHEN 'missing' THEN 0 WHEN 'auto' THEN 1 ELSE 2 END")
            ->orderBy('source')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'items'  => $items,
            'counts' => Translation::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'can_auto_translate' => (bool) Setting::get('integrations.google_translate_key'),
        ]);
    }

    /** Saving a Bangla line by hand marks it as reviewed. */
    public function update(Request $request, Translation $translation)
    {
        $data = $request->validate(['bn' => ['nullable', 'string', 'max:5000']]);
        $bn = trim((string) ($data['bn'] ?? ''));

        $translation->update([
            'bn'     => $bn === '' ? null : $bn,
            'status' => $bn === '' ? 'missing' : 'reviewed',
        ]);

        return $translation;
    }

    /** Approve machine translations as they are. Body: { ids: [...] } */
    public function approve(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        Translation::whereIn('id', $data['ids'])->whereNotNull('bn')->update(['status' => 'reviewed']);
        Translation::flush();

        return response()->json(['message' => 'Approved.']);
    }

    public function destroy(Translation $translation)
    {
        $translation->delete();

        return response()->noContent();
    }

    /** Find new interface text in the Vue files. */
    public function scan()
    {
        Artisan::call('translations:scan');

        return response()->json(['message' => trim(Artisan::output())]);
    }

    /**
     * Fill missing Bangla lines with Google Translate (needs an API key in Settings -> Integrations).
     * Results are marked "auto" so a person can check them. Up to 100 texts per click.
     */
    public function autoTranslate(Request $request)
    {
        $key = Setting::get('integrations.google_translate_key');
        if (! $key) {
            return response()->json(['message' => 'Add a Google Translate API key in Settings > Integrations first.'], 422);
        }

        $rows = Translation::where('status', 'missing')->orderBy('id')->limit(100)->get();
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Nothing is missing.', 'translated' => 0]);
        }

        $response = Http::asForm()->timeout(30)->post('https://translation.googleapis.com/language/translate/v2', [
            'key'    => $key,
            'source' => 'en',
            'target' => 'bn',
            'format' => 'text',
            'q'      => $rows->pluck('source')->all(),
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Google Translate refused the request: ' . $response->json('error.message', 'unknown error')], 502);
        }

        foreach ($response->json('data.translations', []) as $i => $t) {
            $rows[$i]?->update(['bn' => html_entity_decode($t['translatedText'] ?? '', ENT_QUOTES), 'status' => 'auto']);
        }

        return response()->json(['message' => "Translated {$rows->count()} texts. Please check them.", 'translated' => $rows->count()]);
    }
}
