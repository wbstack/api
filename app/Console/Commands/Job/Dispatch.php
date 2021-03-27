<?php

namespace App\Console\Commands\Job;

use Illuminate\Console\Command;

/**
 * From https://stackoverflow.com/questions/43357472/how-to-manually-run-a-laravel-lumen-job-using-command-line.
 *
 * Example: wbs-job:dispatch InvitationCreateJob someCode
 */
class Dispatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbs-job:dispatch {job} {parameter?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a job to the job queue';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $prefix = '\\App\Jobs\\';

        $jobClassName = trim($this->argument('job'));
        if (stripos($jobClassName, '/')) {
            $jobClassName = str_replace('/', '\\', $jobClassName);
        }
        $class = $prefix.$jobClassName;

        if (! class_exists($class)) {
            $this->error("{$class} class Not exists");
        } else {
            if ($this->argument('parameter')) {
                $job = new $class($this->argument('parameter'));
            } else {
                $job = new $class();
            }

            dispatch($job);
            $this->info("Successfully Dispatch {$class} ");
        }
        return 0;
    }
}
