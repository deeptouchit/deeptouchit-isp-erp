<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationScopeService
{
    public const TENANT_TYPES = [
        'bangla_qr_pending',
        'reseller_recharge_pending',
        'reseller_recharge_status',
        'ticket_reply',
        'ticket_activity',
        'ticket_reply_owner',
        'ticket_created',
        'ticket_updated',
        'ticket_status',
        'support_escalation',
        'field_job',
        'installation',
    ];

    public function canReceive(User $user, string $type): bool
    {
        if ($user->isOwner() || $user->isIspAdmin()) {
            return true;
        }

        if ($user->isResellerUser()) {
            return in_array($type, [
                'ticket_reply',
                'ticket_activity',
                'reseller_recharge_status',
                'reseller_recharge_pending',
                'field_job',
                'installation',
            ], true);
        }

        if ($user->isTechnician()) {
            return in_array($type, [
                'ticket_reply',
                'ticket_activity',
                'ticket_status',
                'support_escalation',
                'field_job',
                'installation',
            ], true) && (
                $user->hasPermission('support.tickets')
                || $user->hasPermission('support.field_jobs')
                || $user->hasPermission('support.installations')
                || $user->hasPermission('support.escalations')
            );
        }

        if ($user->isCollector()) {
            return in_array($type, [
                'ticket_reply',
                'ticket_activity',
                'ticket_status',
            ], true) && $user->hasPermission('support.tickets');
        }

        return match ($type) {
            'bangla_qr_pending' =>
                $user->hasPermission('billing.gateways')
                || $user->hasPermission('billing.collect'),
            'reseller_recharge_pending', 'reseller_recharge_status' =>
                $user->hasPermission('resellers.wallet')
                || $user->hasPermission('billing.wholesale'),
            'ticket_reply', 'ticket_activity', 'ticket_reply_owner',
            'ticket_created', 'ticket_updated', 'ticket_status',
            'support_escalation', 'field_job', 'installation' =>
                $user->hasPermission('support.tickets')
                || $user->hasPermission('support.field_jobs')
                || $user->hasPermission('support.installations')
                || $user->hasPermission('support.escalations'),
            default => true,
        };
    }

    public function filterRecipients(Collection $users, string $type): Collection
    {
        return $users->filter(fn (User $user) => $this->canReceive($user, $type))->values();
    }
}
