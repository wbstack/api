<?php

/* The purpose of this class to offer helper functions for safely
 * encoding/decoding domain names that contain non-ASCII characters.
 * 
 * Currently this just wraps around the PHP IDN functions:
 * https://www.php.net/manual/en/ref.intl.idn.php
 * 
 * The difference is that this wrapper returns a string in any case,
 * either encoded or decoded. The native functions can return
 * boolean `false` on failure.
 * 
 */

namespace App\Helper;

class DomainHelper {
    // @return string - the domain name encoded in ASCII-compatible form
    static public function encode($string) {
        $result = idn_to_ascii($string);

        // return the original input if encoding failed
        if (false === $result) {
            $result = $string;
        }

        return $result;
    }

    // @return string - the domain name in Unicode, encoded in UTF-8
    static public function decode($string) {
        $result = idn_to_utf8($string);

        // return the original input if decoding failed
        if (false === $result) {
            $result = $string;
        }

        return $result;
    }
}
