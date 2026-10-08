<?php

namespace Tests\Architecture;

use App\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class JobsInheritBaseClassTest extends TestCase {
    #[DataProvider('provideQueuedJobClasses')]
    public function testQueuedJobsExtendApplicationJob(string $jobClass): void {
        $this->assertTrue(is_subclass_of($jobClass, Job::class));
    }

    public static function provideQueuedJobClasses(): iterable {
        $jobsPath = realpath(dirname(__DIR__, 2) . '/app/Jobs');
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jobsPath));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
            }
        }

        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            $fileName = $reflection->getFileName();

            if (
                $fileName !== false
                && str_starts_with($fileName, $jobsPath . DIRECTORY_SEPARATOR)
                && $class !== Job::class
                && $reflection->implementsInterface(ShouldQueue::class)
            ) {
                yield $class => [$class];
            }
        }
    }
}
