<?php

namespace App\Console\Commands\Invitation;

use App\Jobs\InvitationCreateJob;
use Illuminate\Console\Command;

class Create extends Command
{
    protected $signature = 'wbs-invitation:create {code}';

    protected $description = 'Create an invitation';

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
        $code = trim($this->argument('code'));
        $jobResult = (new InvitationCreateJob( $code ))->handle();

        if( $jobResult ) {
            $this->line( 'Successfully created invitation: ' . $code );
        } else {
            $this->line( 'Failed to create invitation: ' . $code );
        }
    }
}
