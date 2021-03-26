<?php

namespace App\Console\Commands\Job;

use Illuminate\Console\Command;

/**
 * Example: wbs-job:handle InvitationCreateJob someCode
 */
class Handle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbs-job:handle {job} {parameter?} {parameterSeparator?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle a job right now';

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
                if ($this->argument('parameterSeparator')) {
                    $params = explode($this->argument('parameterSeparator'), $this->argument('parameter'));
                    $job = new $class(...$params);
                } else {
                    $job = new $class($this->argument('parameter'));
                }
            } else {
                $job = new $class();
            }

            $job->handle();
            $this->info("Successfully Handled {$class} ");
        }
        return 0;
    }
}
