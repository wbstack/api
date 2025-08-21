<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use App\WikiSetting;
use Illuminate\Console\Command;

class SetSetting extends Command {
    protected $signature = 'wbs-wiki:setSetting {wikiKey} {wikiValue} {settingKey} {settingValue?}';

    protected $description = 'Set a single setting for a wiki.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $wikiKey = trim($this->argument('wikiKey'));
        $wikiValue = trim($this->argument('wikiValue'));
        $settingKey = trim($this->argument('settingKey'));
        $settingValue = $this->argument('settingValue');
        if (is_string($settingValue)) {
            $settingValue = trim($settingValue);
        }

        // TODO don't select the timestamps and redundant info for the settings?
        $wiki = Wiki::where($wikiKey, $wikiValue)->first();
        if (!$wiki) {
            $this->error('Wiki not found');

            return 1;
        }
        $wikiId = $wiki->id;

        if ($settingValue === null) {
            WikiSetting::where([
                'wiki_id' => $wiki->id,
                'name' => $settingKey,
            ])->delete();
            $this->line("Deleted setting {$settingKey} for wiki id {$wikiId}");

            return;
        }

        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wiki->id,
                'name' => $settingKey,
            ],
            [
                'value' => $settingValue,
            ]
        );
        $this->line("Set setting {$settingKey} to {$settingValue} for wiki id {$wikiId}");

        return 0;
    }
}
