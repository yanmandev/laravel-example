<?php

declare(strict_types=1);

return [

    /*
    | Maximum number of projects a single user may own.
    */
    'max_per_user' => (int) env('PROJECTS_MAX_PER_USER', 5),

    'tokens' => [

        /*
        | Maximum number of API tokens per project (active and inactive).
        */
        'max_per_project' => (int) env('PROJECT_TOKENS_MAX_PER_PROJECT', 10),

        /*
        | Token lifetime in days.
        */
        'ttl_days' => (int) env('PROJECT_TOKENS_TTL_DAYS', 365),

        /*
        | `last_used_at` is written at most once per this interval, so that
        | every authenticated request does not end up with an extra UPDATE.
        */
        'last_used_throttle_seconds' => (int) env('PROJECT_TOKENS_LAST_USED_THROTTLE', 60),

    ],

];
