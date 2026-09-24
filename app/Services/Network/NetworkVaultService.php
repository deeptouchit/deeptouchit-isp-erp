<?php

namespace App\Services\Network;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class NetworkVaultService
{
    /**
     * Generate secure 1-click MikroTik Terminal script for a Tenant
     */
    public static function generateMikrotikBootstrapScript(
        string $tenantSlug,
        string $routerName,
        string $apiUsername,
        string $apiPassword,
        int $apiPort = 8728,
        ?string $radiusSecret = null,
        ?string $wireguardIp = null,
        ?string $wireguardPrivateKey = null,
        string $rosVersion = 'v7'
    ): string {
        $radiusServerIp = Setting::get('network_radius_master_host', '10.50.0.1');
        $wireguardServerEndpoint = Setting::get('network_wireguard_endpoint', 'vpn.somitysoft.com:51820');
        $wireguardServerPubKey = Setting::get('network_wireguard_server_pubkey', 'SS_SERVER_PUBKEY_PLACEHOLDER');

        $script = "# ==========================================================\n";
        $script .= "# SomitySoft Carrier-Grade ISP Platform - MikroTik Bootstrap\n";
        $script .= "# Tenant: {$tenantSlug} | Router: {$routerName}\n";
        $script .= "# Generated at: " . now()->toIso8601String() . "\n";
        $script .= "# ==========================================================\n\n";

        // 1. Least-Privilege API Group & User Setup
        $script .= "# 1. Least-Privilege API Security Group & User\n";
        $script .= "/user group add name=\"somitysoft_api\" policy=\"api,read,write,test,!password,!policy,!sensitive\" comment=\"Least-Privilege Group for SomitySoft SaaS\"\n";
        $script .= "/user add name=\"{$apiUsername}\" group=\"somitysoft_api\" password=\"{$apiPassword}\" comment=\"SomitySoft Automation Controller\"\n\n";

        // 2. Enable API Service on customized port
        $script .= "# 2. RouterOS API Service Configuration\n";
        $script .= "/ip service set api port={$apiPort} disabled=no\n\n";

        // 3. RADIUS Configuration (if enabled)
        if (!empty($radiusSecret)) {
            $script .= "# 3. AAA Engine - FreeRADIUS Client Configuration\n";
            $script .= "/radius add service=ppp,hotspot address={$radiusServerIp} secret=\"{$radiusSecret}\" authentication-port=1812 accounting-port=1813 timeout=3000ms comment=\"SomitySoft AAA Cluster\"\n";
            $script .= "/radius incoming set accept=yes port=3799\n";
            $script .= "/ppp aaa set use-radius=yes accounting=yes interim-update=5m\n\n";
        }

        // 4. Secure Connectivity Engine - WireGuard Setup (RouterOS v7)
        if ($rosVersion === 'v7' && !empty($wireguardIp) && !empty($wireguardPrivateKey)) {
            $script .= "# 4. Secure Connectivity Engine - WireGuard NAT Traversal (ROS v7)\n";
            $script .= "/interface wireguard add name=\"wg-somitysoft\" listen-port=13231 private-key=\"{$wireguardPrivateKey}\" comment=\"SomitySoft Encrypted Management Tunnel\"\n";
            $script .= "/ip address add address=\"{$wireguardIp}/30\" interface=\"wg-somitysoft\" network=\"" . substr($wireguardIp, 0, strrpos($wireguardIp, '.')) . ".0\"\n";
            $script .= "/interface wireguard peers add interface=\"wg-somitysoft\" public-key=\"{$wireguardServerPubKey}\" endpoint-address=\"" . explode(':', $wireguardServerEndpoint)[0] . "\" endpoint-port=" . (explode(':', $wireguardServerEndpoint)[1] ?? '51820') . " allowed-address=10.50.0.0/16 persistent-keepalive=25s comment=\"SomitySoft SaaS Gateway\"\n";
            $script .= "/ip firewall filter add chain=input in-interface=wg-somitysoft action=accept comment=\"Allow Management via SomitySoft Tunnel\"\n\n";
        }

        $script .= "# System Bootstrap Complete! Test connection from SomitySoft Console.\n";

        return $script;
    }

    /**
     * Mask sensitive strings (passwords, SNMP community)
     */
    public static function maskSecret(?string $secret, int $visibleChars = 2): string
    {
        if (empty($secret)) {
            return '••••••••';
        }
        $len = strlen($secret);
        if ($len <= 4) {
            return '••••';
        }
        return substr($secret, 0, $visibleChars) . str_repeat('•', max(4, $len - ($visibleChars * 2))) . substr($secret, -$visibleChars);
    }
}
