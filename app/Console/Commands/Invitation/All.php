<?php

namespace App\Console\Commands\Invitation;

use App\Invitation;
use Illuminate\Console\Command;

class All extends Command
{
    protected $signature = 'wbs-invitation:all';

    protected $description = 'List all current invitations';

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
        foreach( Invitation::all() as $invitation ) {
            $this->line($invitation->code);
        }
    }
}
