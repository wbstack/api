<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HandleJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:handle {job} {parameter?} {parameterSeperator?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle job';

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
                if ($this->argument('parameterSeperator')) {
                    $params = explode($this->argument('parameterSeperator'), $this->argument('parameter'));
                    $job = new $class(...$params);
                } else {
                    $job = new $class($this->argument('parameter'));
                }
            } else {
                $job = new $class();
            }

            $job->handle();
            $this->info("Successfully Handeled {$class} ");
        }
    }
}
