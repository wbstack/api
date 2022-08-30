<?php
namespace App\Helper;

class InviteHelper
{
    public function __construct( int $numSegments = 2, int $segmentLength = 4)
    {
        $this->prefix = "wbstack-";
        $this->segments = $numSegments;
        $this->segmentLength = $segmentLength;
    }

    private function generateSegment( int &$counter ) {
        $segment = "";

        for($i = 0; $i < $this->segmentLength; $i++) {
            $segment .= rand(0, 9); 
        }

        $counter++;
        
        if($counter < $this->segments) {
            return $segment . '-' . $this->generateSegment( $counter );
        }

        return $segment;
    }

    public function generate(): string {
        $counter = 0;
        return $this->prefix . $this->generateSegment( $counter );
    }

    
}
