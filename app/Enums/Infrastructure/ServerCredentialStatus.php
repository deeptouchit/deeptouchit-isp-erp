<?php

namespace App\Enums\Infrastructure;

enum ServerCredentialStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
