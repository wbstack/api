<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use Illuminate\Console\Command;

class Delete extends Command
{
    public const ERR_WIKI_DOES_NOT_EXIST = 'No wiki was found matching the given key and value.';
    public const ERR_AMBIGUOUS_KEY_VALUE = 'Wiki deletion failed. Multiple wikis match the given key and value.';

    protected $signature = 'wbs-wiki:delete {key} {value}';
    protected $description = 'Soft deletes the Wiki matching the given key and value.';

    public function handle()
    {
        $key = trim($this->argument('key'));
        $value = trim($this->argument('value'));

        $wikis = Wiki::where($key, $value);

        if ($wikis->count() === 0) {
            $this->error(self::ERR_WIKI_DOES_NOT_EXIST);

            return 1;
        } elseif ($wikis->count() > 1) {
            $this->error(self::ERR_AMBIGUOUS_KEY_VALUE);

            return 1;
        }

        $wikis->delete();

        return 0;
    }
}
