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
        $pattern = '/(?<![\w.])\$?t\(\s*([\'"])((?:\\\\.|(?!\1).)+?)\1\s*[,)]/s';
        $found = [];

        $files = (new Finder())->files()->in(resource_path('js'))->name(['*.vue', '*.js']);
        foreach ($files as $file) {
            if (preg_match_all($pattern, $file->getContents(), $m)) {
                foreach ($m[2] as $text) {
                    $found[stripcslashes($text)] = true;
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
