<?php

namespace App\Enums\Infrastructure;

enum ServerEnvironment: string
{
    case PRODUCTION = 'production';
    case STAGING = 'staging';
    case DEVELOPMENT = 'development';
    case TESTING = 'testing';
}
