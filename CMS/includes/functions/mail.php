<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * E-Mail senden über den zentralen MailService (SMTP oder mail()-Fallback).
 *
 * @param string $to      Empfänger
 * @param string $subject Betreff
 * @param string $body    HTML- oder Plain-Text-Inhalt
 * @param array  $headers Zusätzliche Header
 */
function cms_mail(string $to, string $subject, string $body, array $headers = []): bool {
    $queue = \CMS\Services\MailQueueService::getInstance();
    if ($queue->shouldQueue($headers)) {
        $result = $queue->enqueue($to, $subject, $body, $headers, null, 'cms_mail_helper');
        return !empty($result['success']);
    }

    return \CMS\Services\MailService::getInstance()->send($to, $subject, $body, $headers);
}
