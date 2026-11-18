<?php

return [
    'signal' => [
        'good_min_dbm' => (float) env('ONU_GOOD_MIN_DBM', -20),
        'warning_min_dbm' => (float) env('ONU_WARNING_MIN_DBM', -26),
    ],
];
