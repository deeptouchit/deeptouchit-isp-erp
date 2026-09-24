<?php

namespace App\Enums\Infrastructure;

enum ServerEventType: string
{
    case SERVER_REGISTERED = 'server_registered';
    case SERVER_VERIFIED = 'server_verified';
    case SERVER_OFFLINE = 'server_offline';
    case SERVER_ONLINE = 'server_online';
    case SERVER_MAINTENANCE_STARTED = 'server_maintenance_started';
    case SERVER_MAINTENANCE_ENDED = 'server_maintenance_ended';
    case SERVER_PROVISIONING_STARTED = 'server_provisioning_started';
    case SERVER_PROVISIONING_FAILED = 'server_provisioning_failed';
    case SERVICE_STARTED = 'service_started';
    case SERVICE_STOPPED = 'service_stopped';
    case SERVICE_FAILED = 'service_failed';
    case HEALTH_WARNING = 'health_warning';
    case HEALTH_CRITICAL = 'health_critical';
    case AGENT_CONNECTED = 'agent_connected';
    case AGENT_DISCONNECTED = 'agent_disconnected';
    case AGENT_AUTH_FAILED = 'agent_auth_failed';
    case AGENT_REPLAY_REJECTED = 'agent_replay_rejected';
    case AGENT_SIGNATURE_FAILED = 'agent_signature_failed';
    case SSH_CONNECTION_SUCCESS = 'ssh_connection_success';
    case SSH_CONNECTION_FAILED = 'ssh_connection_failed';
    case SSH_HOST_KEY_MISMATCH = 'ssh_host_key_mismatch';
    case SSH_AUTHENTICATION_FAILED = 'ssh_authentication_failed';
}
