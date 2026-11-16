<?php

namespace App\Panels;

class IbsngPanel extends BasePanel
{
    public function testConnection(): array
    {
        $res = $this->request('/IBSng/admin/', 'GET');
        if ($res['success'] || $res['code'] === 200 || $res['code'] === 302) {
            return [
                'success' => true,
                'message' => 'اتصال به پنل اکانتینگ IBSng برقرار شد.',
            ];
        }

        return [
            'success' => false,
            'message' => 'اتصال به IBSng ناموفق است. آدرس پنل را بررسی کنید.',
        ];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'IBSng Groups']];
    }

    public function createClient(array $params): array
    {
        $username = $params['email'] ?: ('ibs_' . substr(md5(uniqid()), 0, 6));
        $password = substr(md5(uniqid()), 0, 8);

        // IBSng user creation call
        $payload = [
            'auth_name' => $this->panel['username'],
            'auth_pass' => $this->panel['password'],
            'auth_type' => 'ADMIN',
            'count' => 1,
            'credit' => $params['traffic_gb'] ?? 30,
            'owner_name' => $this->panel['username'],
            'group_name' => 'default',
        ];

        $this->request('/IBSng/admin/user/add_new_users', 'POST', $payload);

        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $configText = "🌐 سرور سیسکو / AnyConnect / OpenConnect:\nآدرس سرور: {$host}\nنام کاربری: {$username}\nرمز عبور: {$password}";

        return [
            'success' => true,
            'uuid' => $username,
            'email' => $username,
            'sub_url' => '',
            'config_link' => $configText,
            'wireguard_conf' => null,
            'inbound_id' => 0,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        return null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        return true;
    }
}
