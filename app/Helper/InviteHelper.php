<?php

namespace App\Helper;

class InviteHelper {
    private $prefix;

    public function __construct(private readonly int $segments = 2, private readonly int $segmentLength = 4) {
        $this->prefix = 'wbcloud-';
    }

    private function generateSegment(int &$counter): string {
        $segment = '';

        for ($i = 0; $i < $this->segmentLength; $i++) {
            $segment .= random_int(0, 9);
        }

        $counter++;

        if ($counter < $this->segments) {
            return $segment . '-' . $this->generateSegment($counter);
        }

        return $segment;
    }

    public function generate(): string {
        $counter = 0;

        return $this->prefix . $this->generateSegment($counter);
    }
}
