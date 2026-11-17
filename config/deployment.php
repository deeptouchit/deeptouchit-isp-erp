<?php

return [
    'secret' => env('GITHUB_WEBHOOK_SECRET'),
    'repository' => env('GITHUB_DEPLOY_REPOSITORY', 'deeptouchit/deeptouchit-isp-erp'),
    'branch' => env('GITHUB_DEPLOY_BRANCH', 'refs/heads/main'),
    'runner' => env('GITHUB_DEPLOY_RUNNER'),
];
