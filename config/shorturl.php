<?php

return [
    'default_expiration_days' => env('SHORTURL_EXPIRE_DAYS', 7),
    'default_geo_expiration_seconds' => env('SHORTURL_GEO_EXPIRE_SECONDS', 2592000),
];
