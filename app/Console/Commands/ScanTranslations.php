<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Finds every $t('...') / t('...') text in the Vue and JS files and adds new ones to the translations list.
 */
class ScanTranslations extends Command
{
    protected $signature = 'translations:scan';

    protected $description = 'Add interface texts used with $t() to the translations list';

    public function handle(): int
    {
        $patterns = [
            // $t("Add to cart") and t("Add to cart")
            '/(?<![\w.])\$?t\(\s*([\'"])((?:\\\\.|(?!\1).)+?)\1\s*[,)]/s',
            // Texts passed to components that translate them: <PageHeader title="Orders" />
            '/\s(?:title|subtitle|lead|text|label|hint|confirm-text)=(")([^"{}<>]{2,})"/',
            // Texts in menus, settings lists and route titles: { title: "Orders" }
            '/\b(?:title|subtitle|label|hint|suffix|lead)\s*:\s*(")([^"{}<>]{2,})"/',
        ];
        $found = [];

        $files = (new Finder())->files()->in(resource_path('js'))->exclude('_legacy')->name(['*.vue', '*.js']);
        foreach ($files as $file) {
            $code = $file->getContents();
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $code, $m)) {
                    foreach ($m[2] as $text) {
                        $text = stripcslashes($text);
                        // Skip things that are clearly not words: icons, paths, CSS classes.
                        if (! preg_match('/^(mdi-|\/|#|\.|https?:)/', $text) && preg_match('/[A-Za-z]/', $text)) {
                            $found[$text] = true;
                        }
                    }
                }
            }
        }

        $before = Translation::count();
        foreach (array_keys($found) as $text) {
            Translation::register($text, 'ui');
        }
        $added = Translation::count() - $before;

        $this->info("Found " . count($found) . " interface texts, {$added} new.");

        return self::SUCCESS;
    }
}
