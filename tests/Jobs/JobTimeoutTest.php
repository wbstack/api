<?php

namespace Tests\Jobs;

use App\Jobs\Job;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Tests\TestCase;

class JobTimeoutTest extends TestCase {
    public function testRetryAfterIsAtLeastFiveSecondsLongerThanEveryJobTimeout() {
        $retryAfter = config('queue.connections.redis.retry_after');
        $timeouts = [
            ...$this->jobTimeouts(),
            ...$this->horizonTimeouts(),
            ...$this->queueWorkerTimeouts(),
        ];

        $this->assertIsInt($retryAfter);
        $this->assertNotEmpty($timeouts);

        foreach ($timeouts as $source => $timeout) {
            $this->assertIsInt($timeout, "{$source} must be an integer number of seconds.");
            $this->assertGreaterThanOrEqual(
                $timeout + 5,
                $retryAfter,
                "queue.connections.redis.retry_after must be at least 5 seconds longer than {$source} ({$timeout} seconds).",
            );
        }
    }

    private function jobTimeouts(): array {
        $timeouts = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Jobs')));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen(app_path()) + 1, -4);
            $class = 'App\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (!class_exists($class) || !is_a($class, Job::class, true)) {
                continue;
            }

            $properties = (new ReflectionClass($class))->getDefaultProperties();

            if (array_key_exists('timeout', $properties) && $properties['timeout'] !== null) {
                $timeouts["{$class}::\$timeout"] = $properties['timeout'];
            }
        }

        return $timeouts;
    }

    private function horizonTimeouts(): array {
        return $this->findTimeouts(config('horizon'), 'horizon');
    }

    private function findTimeouts(array $config, string $path): array {
        $timeouts = [];

        foreach ($config as $key => $value) {
            $valuePath = "{$path}.{$key}";

            if ($key === 'timeout') {
                $timeouts[$valuePath] = $value;
            } elseif (is_array($value)) {
                $timeouts = array_merge($timeouts, $this->findTimeouts($value, $valuePath));
            }
        }

        return $timeouts;
    }

    private function queueWorkerTimeouts(): array {
        $startScript = file_get_contents(base_path('start.sh'));
        preg_match_all('/--timeout(?:=|\s+)(\d+)/', $startScript, $matches);

        $timeouts = [];
        foreach ($matches[1] as $index => $timeout) {
            $timeouts['start.sh --timeout #' . ($index + 1)] = intval($timeout);
        }

        return $timeouts;
    }
}
