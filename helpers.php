<?php

namespace Garbetjie\RequestLogging\Http;

use EndyJasmi\Cuid;
use function implode;
use function is_array;
use function str_replace;
use function strtolower;

function normalize_headers(array $headers) {
    $formatted = [];

    foreach ($headers as $key => $value) {
        $key = str_replace('_', '-', strtolower($key));

        if (is_array($value)) {
            $formatted[$key] = implode(', ', $value);
        } else {
            $formatted[$key] = $value;
        }
    }

    return $formatted;
}

/**
 * @return string
 */
function generate_id() {
    return Cuid::make();
}
