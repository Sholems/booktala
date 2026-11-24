<?php

return [
    // Coolify terminates TLS at its reverse proxy. Trust proxy headers only when
    // explicitly enabled by the deployment environment.
    'proxies' => env('TRUSTED_PROXIES'),
];
