<?php

namespace App\Console\Commands\Invitation;

use App\Jobs\InvitationDeleteJob;
use Illuminate\Console\Command;

class Delete extends Command
{
    protected $signature = 'wbs-invitation:delete {code}';

    protected $description = 'Delete an invitation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $code = trim($this->argument('code'));
        (new InvitationDeleteJob($code))->handle();

        return 0;
    }
}
