<?php

namespace App\Enums\Infrastructure;

enum ServerAuthType: string
{
    case PASSWORD = 'password';
    case SSH_KEY = 'ssh_key';
    case AGENT = 'agent';
}
