<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Services\AzureMailTokenProvider;
use CMS\Services\GraphApiService;
use CMS\Services\MailLogService;
use CMS\Services\MailService;
use CMS\Services\SettingsService;

class MailSettingsModule
{
    private SettingsService $settings;
    private MailLogService $mailLogs;

    public function __construct()
    {
        $this->settings = SettingsService::getInstance();
        $this->mailLogs = MailLogService::getInstance();
    }

    public function getData(): array
    {
        $mail = $this->settings->getGroup('mail');
        $graph = $this->settings->getGroup('graph');
        $transportInfo = MailService::getInstance()->getTransportInfo();
        $recentLogs = $this->mailLogs->getRecent(50);
        $stats = $this->mailLogs->getStats();
        $azure = AzureMailTokenProvider::getInstance()->getConfiguration();
        $graphConfig = GraphApiService::getInstance()->getConfiguration();

        return [
            'transport' => [
                'driver' => (string) ($mail['driver'] ?? ($transportInfo['uses_smtp'] ? 'smtp' : 'mail')),
                'auth_mode' => (string) ($mail['auth_mode'] ?? ($transportInfo['auth_mode'] ?? 'password')),
                'smtp_host' => (string) ($mail['smtp_host'] ?? ($transportInfo['host'] ?? '')),
                'smtp_port' => (int) ($mail['smtp_port'] ?? ($transportInfo['port'] ?? 587)),
                'smtp_encryption' => (string) ($mail['smtp_encryption'] ?? ($transportInfo['encryption_raw'] ?? 'tls')),
                'smtp_username' => (string) ($mail['smtp_username'] ?? ($transportInfo['username'] ?? '')),
                'from_email' => (string) ($mail['from_email'] ?? ($transportInfo['from_email'] ?? '')),
                'from_name' => (string) ($mail['from_name'] ?? ($transportInfo['from_name'] ?? '')),
                'secret_configured' => $this->settings->getString('mail', 'smtp_password') !== '',
                'test_recipient' => (string) ($mail['test_recipient'] ?? ($transportInfo['from_email'] ?? (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : ''))),
            ],
            'azure' => [
                'tenant_id' => (string) ($mail['azure_tenant_id'] ?? ''),
                'client_id' => (string) ($mail['azure_client_id'] ?? ''),
                'client_secret_configured' => $this->settings->getString('mail', 'azure_client_secret') !== '',
                'mailbox' => (string) ($mail['azure_mailbox'] ?? ''),
                'scope' => (string) ($mail['azure_scope'] ?? $azure['scope']),
                'token_endpoint' => (string) ($mail['azure_token_endpoint'] ?? $azure['token_endpoint']),
                'configured' => $azure['configured'],
            ],
            'graph' => [
                'tenant_id' => (string) ($graph['tenant_id'] ?? ''),
                'client_id' => (string) ($graph['client_id'] ?? ''),
                'client_secret_configured' => $this->settings->getString('graph', 'client_secret') !== '',
                'scope' => (string) ($graph['scope'] ?? $graphConfig['scope']),
                'base_url' => (string) ($graph['base_url'] ?? $graphConfig['base_url']),
                'token_endpoint' => (string) ($graph['token_endpoint'] ?? $graphConfig['token_endpoint']),
                'configured' => $graphConfig['configured'],
            ],
            'transport_info' => $transportInfo,
            'mail_logs' => $recentLogs,
            'mail_stats' => $stats,
            'queue_stats' => [
                'pending' => (int) ($stats['queued_pending'] ?? 0),
                'failed' => (int) ($stats['queued_failed'] ?? 0),
            ],
        ];
    }

    public function saveTransport(array $post): array
    {
        $driver = ($post['driver'] ?? 'mail') === 'smtp' ? 'smtp' : 'mail';
        $authMode = ($post['auth_mode'] ?? 'password') === 'oauth2' ? 'oauth2' : 'password';
        $smtpEncryption = (string) ($post['smtp_encryption'] ?? 'tls');
        if (!in_array($smtpEncryption, ['tls', 'ssl', ''], true)) {
            $smtpEncryption = 'tls';
        }

        $values = [
            'driver' => $driver,
            'auth_mode' => $authMode,
            'smtp_host' => trim((string) ($post['smtp_host'] ?? '')),
            'smtp_port' => max(1, min(65535, (int) ($post['smtp_port'] ?? 587))),
            'smtp_encryption' => $smtpEncryption,
            'smtp_username' => trim((string) ($post['smtp_username'] ?? '')),
            'from_email' => filter_var((string) ($post['from_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'from_name' => trim(strip_tags((string) ($post['from_name'] ?? ''))),
            'test_recipient' => filter_var((string) ($post['test_recipient'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
        ];

        if (!$this->settings->setMany('mail', $values, [], 0)) {
            return ['success' => false, 'error' => 'Transport-Einstellungen konnten nicht gespeichert werden.'];
        }

        $smtpPassword = trim((string) ($post['smtp_password'] ?? ''));
        if ($smtpPassword !== '') {
            $this->settings->set('mail', 'smtp_password', $smtpPassword, true, 0);
        }

        if (!empty($post['clear_smtp_password'])) {
            $this->settings->forget('mail', 'smtp_password');
        }

        if ($authMode !== 'oauth2') {
            AzureMailTokenProvider::getInstance()->clearCache();
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'setting.mail.transport.save',
            'Mail-Transport-Einstellungen gespeichert.',
            'setting',
            null,
            [
                'driver' => $driver,
                'auth_mode' => $authMode,
                'smtp_host' => $values['smtp_host'],
                'smtp_port' => $values['smtp_port'],
            ],
            'info'
        );

        return ['success' => true, 'message' => 'Transport-Einstellungen gespeichert.'];
    }

    public function saveAzure(array $post): array
    {
        $values = [
            'azure_tenant_id' => trim((string) ($post['azure_tenant_id'] ?? '')),
            'azure_client_id' => trim((string) ($post['azure_client_id'] ?? '')),
            'azure_mailbox' => filter_var((string) ($post['azure_mailbox'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'azure_scope' => trim((string) ($post['azure_scope'] ?? 'https://outlook.office365.com/.default')),
            'azure_token_endpoint' => trim((string) ($post['azure_token_endpoint'] ?? '')),
        ];

        if (!$this->settings->setMany('mail', $values, [], 0)) {
            return ['success' => false, 'error' => 'Azure-OAuth2-Einstellungen konnten nicht gespeichert werden.'];
        }

        $clientSecret = trim((string) ($post['azure_client_secret'] ?? ''));
        if ($clientSecret !== '') {
            $this->settings->set('mail', 'azure_client_secret', $clientSecret, true, 0);
        }
        if (!empty($post['clear_azure_client_secret'])) {
            $this->settings->forget('mail', 'azure_client_secret');
        }

        AzureMailTokenProvider::getInstance()->clearCache();

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'setting.mail.azure.save',
            'Azure-Mail-OAuth2-Einstellungen gespeichert.',
            'setting',
            null,
            [
                'tenant_id' => $values['azure_tenant_id'],
                'client_id' => $values['azure_client_id'],
                'mailbox' => $values['azure_mailbox'],
            ],
            'info'
        );

        return ['success' => true, 'message' => 'Azure-OAuth2-Einstellungen gespeichert.'];
    }

    public function saveGraph(array $post): array
    {
        $values = [
            'tenant_id' => trim((string) ($post['graph_tenant_id'] ?? '')),
            'client_id' => trim((string) ($post['graph_client_id'] ?? '')),
            'scope' => trim((string) ($post['graph_scope'] ?? 'https://graph.microsoft.com/.default')),
            'base_url' => trim((string) ($post['graph_base_url'] ?? 'https://graph.microsoft.com/v1.0')),
            'token_endpoint' => trim((string) ($post['graph_token_endpoint'] ?? '')),
        ];

        if (!$this->settings->setMany('graph', $values, [], 0)) {
            return ['success' => false, 'error' => 'Graph-Einstellungen konnten nicht gespeichert werden.'];
        }

        $clientSecret = trim((string) ($post['graph_client_secret'] ?? ''));
        if ($clientSecret !== '') {
            $this->settings->set('graph', 'client_secret', $clientSecret, true, 0);
        }
        if (!empty($post['clear_graph_client_secret'])) {
            $this->settings->forget('graph', 'client_secret');
        }

        GraphApiService::getInstance()->clearCache();

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'setting.graph.save',
            'Microsoft-Graph-Einstellungen gespeichert.',
            'setting',
            null,
            [
                'tenant_id' => $values['tenant_id'],
                'client_id' => $values['client_id'],
                'base_url' => $values['base_url'],
            ],
            'info'
        );

        return ['success' => true, 'message' => 'Microsoft-Graph-Einstellungen gespeichert.'];
    }

    public function sendTestEmail(array $post): array
    {
        $recipient = trim((string) ($post['test_email_recipient'] ?? ''));
        $result = MailService::getInstance()->sendBackendTestEmail($recipient, 'admin-mail-settings');

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'setting.mail.test',
            !empty($result['success']) ? 'Test-E-Mail aus dem Mail-Admin versendet.' : 'Test-E-Mail aus dem Mail-Admin fehlgeschlagen.',
            'setting',
            null,
            [
                'recipient' => $recipient,
                'transport' => $result['transport'] ?? null,
                'result' => !empty($result['success']) ? 'success' : 'error',
            ],
            !empty($result['success']) ? 'info' : 'warning'
        );

        return $result;
    }

    public function testGraphConnection(): array
    {
        $result = GraphApiService::getInstance()->testConnection(true);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'setting.graph.test',
            !empty($result['success']) ? 'Microsoft-Graph-Verbindung erfolgreich getestet.' : 'Microsoft-Graph-Verbindungstest fehlgeschlagen.',
            'setting',
            null,
            [
                'result' => !empty($result['success']) ? 'success' : 'error',
                'organization' => $result['organization']['displayName'] ?? null,
            ],
            !empty($result['success']) ? 'info' : 'warning'
        );

        return $result;
    }

    public function clearLogs(): array
    {
        if (!$this->mailLogs->clear()) {
            return ['success' => false, 'error' => 'Mail-Logs konnten nicht geleert werden.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'mail.log.clear',
            'Mail-Logs wurden geleert.',
            'system',
            null,
            [],
            'warning'
        );

        return ['success' => true, 'message' => 'Mail-Logs wurden geleert.'];
    }

    public function clearAzureCache(): array
    {
        AzureMailTokenProvider::getInstance()->clearCache();

        return ['success' => true, 'message' => 'Azure-Token-Cache wurde geleert.'];
    }

    public function clearGraphCache(): array
    {
        GraphApiService::getInstance()->clearCache();

        return ['success' => true, 'message' => 'Graph-Token-Cache wurde geleert.'];
    }
}
