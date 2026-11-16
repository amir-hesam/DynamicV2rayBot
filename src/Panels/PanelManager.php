<?php

namespace App\Panels;

use App\Models\Panel;

class PanelManager
{
    /**
     * Balanced panel types grid (12 panels in 6 clean pairs)
     */
    public static function getPanelTypesGrid(): array
    {
        return [
            ['sanaei' => '3x-ui (سنایی)', 'marzban' => 'مرزبان'],
            ['pasargad' => 'پاسارگاد', 'hiddify' => 'هیدیفای'],
            ['marzneshin' => 'مرزنشین', 'manual' => 'فروش دستی'],
            ['wgdashboard' => 'WGDashboard', 'wgmate' => 'WG-Mate'],
            ['mikrotik' => 'میکروتیک', 'ibsng' => 'ibsng'],
            ['nexora' => 'Nexora', 'rebka' => 'ربکا'],
        ];
    }

    /**
     * Flat key => label list of all supported panels
     */
    public static function getAllPanelTypes(): array
    {
        return [
            'sanaei' => '3x-ui (سنایی)',
            'marzban' => 'مرزبان',
            'pasargad' => 'پاسارگاد',
            'hiddify' => 'هیدیفای',
            'marzneshin' => 'مرزنشین',
            'manual' => 'فروش دستی',
            'wgdashboard' => 'WGDashboard (WireGuard)',
            'wgmate' => 'WG-Mate (WireGuard)',
            'mikrotik' => 'میکروتیک (RouterOS)',
            'ibsng' => 'ibsng (اکانتینگ)',
            'nexora' => 'Nexora',
            'rebka' => 'ربکا',
        ];
    }

    public static function make(array $panel): PanelInterface
    {
        $type = strtolower($panel['type'] ?? 'sanaei');

        return match ($type) {
            'sanaei', '3x-ui' => new SanaeiPanel($panel),
            'marzban' => new MarzbanPanel($panel),
            'pasargad' => new PasargadPanel($panel),
            'hiddify' => new HiddifyPanel($panel),
            'marzneshin' => new MarzneshinPanel($panel),
            'manual' => new ManualPanel($panel),
            'wgdashboard' => new WGDashboardPanel($panel),
            'wgmate' => new WgMatePanel($panel),
            'mikrotik' => new MikrotikPanel($panel),
            'ibsng' => new IbsngPanel($panel),
            'nexora' => new NexoraPanel($panel),
            'rebka' => new RebkaPanel($panel),
            default => new SanaeiPanel($panel),
        };
    }

    public static function makeById(int $panelId): ?PanelInterface
    {
        $panel = Panel::find($panelId);
        if (!$panel) {
            return null;
        }

        return self::make($panel);
    }
}
