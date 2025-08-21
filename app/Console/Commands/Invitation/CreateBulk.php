<?php

namespace App\Console\Commands\Invitation;

use App\Helper\InviteHelper;
use App\Jobs\InvitationCreateJob;
use Illuminate\Console\Command;

class CreateBulk extends Command {
    protected $signature = 'wbs-invitation:create-bulk {numCodes}';

    protected $description = 'Create an bulk invitations';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $numCodes = trim($this->argument('numCodes'));
        $helper = new InviteHelper;

        for ($i = 0; $i < $numCodes; $i++) {
            $code = $helper->generate();
            $jobResult = (new InvitationCreateJob($code))->handle();

            if ($jobResult) {
                $this->line('Successfully created invitation: ' . $code);
            } else {
                $this->line('Failed to create invitation: ' . $code);
            }
        }

        return 0;
    }
}
