<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class SidebarMenuService
{
    /**
     * Resolve and filter navigation menus cleanly for the authenticated tenant user.
     */
    public static function resolveForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        $rawMenus = self::loadConfigMenusForUser($user);
        $resolved = [];

        foreach ($rawMenus as $item) {
            if (!self::userCanSee($user, $item)) {
                continue;
            }

            // Group with sub-menu children
            if (!empty($item['children']) && is_array($item['children'])) {
                $visibleChildren = [];
                $anyChildActive = false;

                foreach ($item['children'] as $child) {
                    if (!self::userCanSee($user, $child)) {
                        continue;
                    }

                    $url = self::resolveUrl($child);
                    $isActive = self::isItemActive($child);
                    if ($isActive) {
                        $anyChildActive = true;
                    }

                    $childColor = $child['active_color'] ?? ($item['color'] ?? 'blue');

                    $visibleChildren[] = [
                        'title'      => $child['title'],
                        'url'        => $url,
                        'is_active'  => $isActive,
                        'badge'      => $child['badge'] ?? null,
                        'keywords'   => mb_strtolower(trim(($child['keywords'] ?? $child['title']) . ' ' . __($child['title'])), 'UTF-8'),
                        'active_cls' => self::getChildActiveClasses($childColor),
                    ];
                }

                // Skip empty parent group when user has no accessible children
                if (empty($visibleChildren)) {
                    continue;
                }

                $parentActive = $anyChildActive || self::isItemActive($item);
                $color = $item['color'] ?? 'blue';
                $childKeywords = implode(' ', array_column($visibleChildren, 'keywords'));
                $combinedKeywords = mb_strtolower(trim(($item['keywords'] ?? $item['title']) . ' ' . __($item['title']) . ' ' . $childKeywords), 'UTF-8');

                $resolved[] = [
                    'type'          => 'group',
                    'title'         => $item['title'],
                    'icon'          => $item['icon'] ?? 'fas fa-circle',
                    'is_active'     => $parentActive,
                    'keywords'      => $combinedKeywords,
                    'active_cls'    => self::getParentActiveClasses($color),
                    'icon_active'   => self::getIconActiveClass($color),
                    'children'      => $visibleChildren,
                ];
            } else {
                // Standalone link item
                $isActive = self::isItemActive($item);
                $color = $item['color'] ?? 'blue';

                $resolved[] = [
                    'type'        => 'link',
                    'title'       => $item['title'],
                    'url'         => self::resolveUrl($item),
                    'icon'        => $item['icon'] ?? 'fas fa-circle',
                    'is_active'   => $isActive,
                    'keywords'    => mb_strtolower(trim(($item['keywords'] ?? $item['title']) . ' ' . __($item['title'])), 'UTF-8'),
                    'active_cls'  => self::getParentActiveClasses($color),
                    'icon_active' => self::getIconActiveClass($color),
                ];
            }
        }

        return $resolved;
    }

    /**
     * Load role-specific config menu tree from config/menus/*.php.
     */
    protected static function loadConfigMenusForUser(User $user): array
    {
        if ($user->isCollector()) {
            return config('menus.collector', []);
        }

        if ($user->isTechnician()) {
            return config('menus.technician', []);
        }

        if ($user->role === 'isp_manager') {
            return config('menus.manager', config('menus.admin', []));
        }

        return config('menus.admin', []);
    }

    /**
     * Determine whether user meets role OR permission criteria for a menu node.
     */
    protected static function userCanSee(User $user, array $item): bool
    {
        if ($user->isOwner() || $user->isIspAdmin()) {
            return true;
        }

        $roles = $item['roles'] ?? [];
        $permissions = $item['permissions'] ?? [];
        if (!empty($item['permission'])) {
            $permissions[] = $item['permission'];
        }

        // Unconditional item within this role's dedicated menu file
        if (empty($roles) && empty($permissions)) {
            return true;
        }

        if (!empty($roles) && $user->hasRole(...$roles)) {
            return true;
        }

        if (!empty($permissions)) {
            foreach ($permissions as $perm) {
                if ($user->hasPermission($perm)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Safely resolve URL with optional fallback route & query parameters.
     */
    protected static function resolveUrl(array $item): string
    {
        $routeName = $item['route'] ?? null;
        $params = $item['params'] ?? [];

        if ($routeName && Route::has($routeName)) {
            return route($routeName, $params);
        }

        $fallbackRoute = $item['fallback_route'] ?? null;
        $fallbackParams = $item['fallback_params'] ?? [];

        if ($fallbackRoute && Route::has($fallbackRoute)) {
            return route($fallbackRoute, $fallbackParams);
        }

        return '#';
    }

    /**
     * Evaluate active state for routes, excluded patterns, and query-param filters.
     */
    protected static function isItemActive(array $item): bool
    {
        // Special Finance Ledger modes
        $mode = $item['active_mode'] ?? null;
        if ($mode === 'due_invoices') {
            return request()->routeIs('tenant.finance.invoices*')
                && !request()->routeIs('tenant.finance.billing-catchup*')
                && in_array(request('status'), ['due', 'unpaid', 'overdue', 'partially_paid'], true);
        }
        if ($mode === 'paid_invoices') {
            $isDue = in_array(request('status'), ['due', 'unpaid', 'overdue', 'partially_paid'], true);
            return request()->routeIs('tenant.finance.invoices*')
                && !request()->routeIs('tenant.finance.billing-catchup*')
                && (request('status') === 'paid' || (!request()->has('status') && !$isDue));
        }

        // Exclusions override route patterns (e.g. All Customers vs Due/Expired tabs)
        foreach (($item['exclude_routes'] ?? []) as $excluded) {
            if (request()->routeIs($excluded)) {
                return false;
            }
        }

        if (!empty($item['require_no_status']) && request()->filled('status')) {
            return false;
        }

        if (!empty($item['not_status_in']) && in_array(request('status'), $item['not_status_in'], true)) {
            return false;
        }

        // Route pattern match
        foreach (($item['active'] ?? []) as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        // Raw URL path match
        foreach (($item['active_paths'] ?? []) as $pathPattern) {
            if (request()->is($pathPattern)) {
                return true;
            }
        }

        // Query status param fallback match (scoped strictly to the item's own routes)
        if (!empty($item['status_in']) && in_array(request('status'), $item['status_in'], true)) {
            $statusRoutes = array_values(array_filter(array_merge(
                (array) ($item['status_routes'] ?? []),
                [$item['route'] ?? null, $item['fallback_route'] ?? null]
            )));

            if (empty($statusRoutes) || request()->routeIs(...$statusRoutes)) {
                return true;
            }
        }

        return false;
    }

    protected static function getParentActiveClasses(string $color): string
    {
        return match ($color) {
            'purple' => 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600',
            'cyan'   => 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600',
            'indigo' => 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600',
            'amber'  => 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600',
            'rose'   => 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600',
            'slate'  => 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700',
            default  => 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600',
        };
    }

    protected static function getChildActiveClasses(string $color): string
    {
        return match ($color) {
            'purple' => 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600',
            'cyan'   => 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600',
            'indigo' => 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600',
            'amber'  => 'border-amber-300 bg-amber-50 text-amber-800 font-bold border-l-4 border-l-amber-600',
            'rose'   => 'border-rose-300 bg-rose-50 text-rose-800 font-bold border-l-4 border-l-rose-600',
            'slate'  => 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700',
            default  => 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600',
        };
    }

    protected static function getIconActiveClass(string $color): string
    {
        return match ($color) {
            'purple' => 'text-purple-600',
            'cyan'   => 'text-cyan-600',
            'indigo' => 'text-indigo-600',
            'amber'  => 'text-amber-600',
            'rose'   => 'text-rose-600',
            'slate'  => 'text-slate-800',
            default  => 'text-blue-600',
        };
    }
}
