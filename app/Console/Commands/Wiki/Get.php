<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use Illuminate\Console\Command;

class Get extends Command
{
    protected $signature = 'wbs-wiki:get {key} {value?}';

    protected $description = 'Get Wiki data by key and value.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $key = trim($this->argument('key'));
        $value = trim($this->argument('value'));
        // TODO don't select the timestamps and redundant info for the settings?
        $result = Wiki::where($key, $value)
            ->with(['wikiDb', 'wikiQueryserviceNamespace', 'settings'])
            ->first();

        $this->line($result->toJson(JSON_PRETTY_PRINT));

        return 0;
    }
}
