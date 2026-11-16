<?php

namespace App\Panels;

interface PanelInterface
{
    /**
     * Test connection to the panel.
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array;

    /**
     * Get available inbounds from the panel.
     * @return array
     */
    public function getInbounds(): array;

    /**
     * Create a new client on the panel with specified limits.
     * @param array $params [
     *     'email' => string,
     *     'uuid' => string,
     *     'traffic_gb' => int,
     *     'days' => int,
     *     'ip_limit' => int,
     *     'inbound_id' => ?int
     * ]
     * @return array ['success' => bool, 'uuid' => string, 'email' => string, 'sub_url' => string, 'config_link' => string, 'inbound_id' => int, 'error' => ?string]
     */
    public function createClient(array $params): array;

    /**
     * Get client information (usage, expiry, status).
     * @param string $clientIdentifier (uuid, email or username)
     * @return array|null
     */
    public function getClient(string $clientIdentifier): ?array;

    /**
     * Delete client from panel.
     * @param int $inboundId
     * @param string $clientIdentifier
     * @return bool
     */
    public function deleteClient(int $inboundId, string $clientIdentifier): bool;
}
