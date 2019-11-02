<?php

namespace App\Console\Commands;

use App\Wiki;
use Illuminate\Console\Command;

class GetWikiForXJob extends Command
{
    protected $signature = 'app:getWiki {key} {value?}';

    protected $description = 'Get Wiki For X job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $key = trim($this->argument('key'));
        $value = trim($this->argument('value'));
        // TODO don't select the timestamps and redundant info for the settings?
        $result = Wiki::where($key, $value)
            ->with(['wikiDb','wikiQueryserviceNamespace','settings'])
            ->first();

        $this->line($result->toJson(JSON_PRETTY_PRINT));
    }
}
