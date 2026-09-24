<?php

namespace App\Enums\Infrastructure;

enum ServerCredentialType: string
{
    case SSH_PASSWORD = 'ssh_password';
    case SSH_PRIVATE_KEY = 'ssh_private_key';
    case AGENT_TOKEN = 'agent_token';
    case API_TOKEN = 'api_token';
    case SUDO_PASSWORD = 'sudo_password';
}
