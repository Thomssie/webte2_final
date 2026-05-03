<?php

return [
    'api_key' => env('CAS_API_KEY', ''),
    'octave_binary' => env('CAS_OCTAVE_BINARY', 'octave'),
    'timeout_seconds' => (int) env('CAS_TIMEOUT_SECONDS', 10),
    'slowdown_ms' => (int) env('CAS_SLOWDOWN_MS', 0),
    'animation_stats_interval_minutes' => (int) env('ANIMATION_STATS_INTERVAL_MINUTES', 10),
];
