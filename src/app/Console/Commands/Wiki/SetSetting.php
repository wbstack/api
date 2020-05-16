<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use App\WikiSetting;
use Illuminate\Console\Command;

class SetSetting extends Command
{
    protected $signature = 'wbs-wiki:setSetting {wikiKey} {wikiValue} {settingKey} {settingValue}';

    protected $description = 'Set a single setting for a wiki.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $wikiKey = trim($this->argument('wikiKey'));
        $wikiValue = trim($this->argument('wikiValue'));
        $settingKey = trim($this->argument('settingKey'));
        $settingValue = trim($this->argument('settingValue'));

        // TODO don't select the timestamps and redundant info for the settings?
        $wiki = Wiki::where($wikiKey, $wikiValue)->first();
        if(!$wiki){
            $this->error('Wiki not found');
            return;
        }
        $wikiId = $wiki->id;

        $setting = WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wiki->id,
                'name' => $settingKey,
            ],
            [
                'value' => $settingValue,
            ]
        );
        $this->line("Set setting ${settingKey} to ${settingValue} for wiki id ${wikiId}");
    }
}
