<?php

return [
    'url' => env('AI_SERVICE_URL', 'http://ai:8001'),
    'token' => env('INTERNAL_AI_TOKEN', ''),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 8),
];
