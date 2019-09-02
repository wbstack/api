<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * From https://stackoverflow.com/questions/43357472/how-to-manually-run-a-laravel-lumen-job-using-command-line.
 */
class DispatchJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:dispatch {job} {parameter?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job';

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
        $prefix = '\\App\Jobs\\';

        $jobClassName = trim($this->argument('job'));
        if (stripos($jobClassName, '/')) {
            $jobClassName = str_replace('/', '\\', $jobClassName);
        }
        $class = '\\App\\Jobs\\'.$jobClassName;

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
    }
}
