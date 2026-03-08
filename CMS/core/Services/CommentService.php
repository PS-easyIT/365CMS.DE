<?php
/**
 * CommentService – Kommentarverwaltung (Frontend + Moderation)
 *
 * Verantwortlich für:
 * - Erstellen neuer Kommentare (pending)
 * - Laden freigegebener Kommentare je Beitrag
 * - Moderation im Admin (approve/spam/trash/delete)
 * - Benachrichtigung per Mail bei neuen wartenden Kommentaren
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

class CommentService
{
    private static ?self $instance = null;

    private Database $db;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Freigegebene Kommentare für einen Beitrag.
     *
     * @return array<object>
     */
    public function getApprovedForPost(int $postId): array
    {
        return $this->db->get_results(
            "SELECT id, author, author_email, content, post_date\n             FROM {$this->prefix}comments\n             WHERE post_id = ? AND status = 'approved'\n             ORDER BY post_date ASC",
            [$postId]
        ) ?: [];
    }

    /**
     * Öffentliche Kommentar-Erstellung (immer als pending).
     */
    public function createPendingComment(
        int $postId,
        string $authorName,
        string $authorEmail,
        string $content,
        string $authorIp = '',
        ?int $userId = null
    ): int|false {
        $authorName = trim(strip_tags($authorName));
        $authorName = mb_substr($authorName, 0, 100);

        $authorEmail = trim($authorEmail);
        $validatedEmail = filter_var($authorEmail, FILTER_VALIDATE_EMAIL);

        $cleanContent = trim(PurifierService::getInstance()->purify($content, 'strict'));

        if ($postId <= 0 || $authorName === '' || $validatedEmail === false || $cleanContent === '') {
            return false;
        }

        $insertId = $this->db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'author' => $authorName,
            'author_email' => (string) $validatedEmail,
            'author_ip' => mb_substr($authorIp, 0, 45),
            'content' => $cleanContent,
            'status' => 'pending',
        ]);

        if ($insertId === false) {
            return false;
        }

        $this->notifyAdminForPendingComment($postId, $authorName, (string) $validatedEmail);

        return $insertId;
    }

    public function getComments(string $status = 'all', int $limit = 50, int $offset = 0): array
    {
        $where = '';
        $params = [];

        if (in_array($status, ['pending', 'approved', 'spam', 'trash'], true)) {
            $where = 'WHERE c.status = ?';
            $params[] = $status;
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->get_results(
            "SELECT c.id, c.post_id, c.author, c.author_email, c.content, c.status, c.post_date,
                    p.title AS post_title, p.slug AS post_slug
             FROM {$this->prefix}comments c
             LEFT JOIN {$this->prefix}posts p ON p.id = c.post_id
             {$where}
             ORDER BY c.post_date DESC
             LIMIT ? OFFSET ?",
            $params
        ) ?: [];
    }

    public function getCounts(): array
    {
        $rows = $this->db->get_results(
            "SELECT status, COUNT(*) AS cnt
             FROM {$this->prefix}comments
             GROUP BY status"
        ) ?: [];

        $counts = [
            'all' => 0,
            'pending' => 0,
            'approved' => 0,
            'spam' => 0,
            'trash' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');
            $cnt = (int) ($row->cnt ?? 0);
            if (isset($counts[$status])) {
                $counts[$status] = $cnt;
            }
            $counts['all'] += $cnt;
        }

        return $counts;
    }

    public function updateStatus(int $commentId, string $status): bool
    {
        if ($commentId <= 0 || !in_array($status, ['pending', 'approved', 'spam', 'trash'], true)) {
            return false;
        }

        return $this->db->update('comments', ['status' => $status], ['id' => $commentId]);
    }

    public function delete(int $commentId): bool
    {
        if ($commentId <= 0) {
            return false;
        }

        return $this->db->delete('comments', ['id' => $commentId]);
    }

    private function notifyAdminForPendingComment(int $postId, string $author, string $email): void
    {
        $to = defined('ADMIN_EMAIL') ? (string) ADMIN_EMAIL : '';
        if ($to === '') {
            return;
        }

        $postTitle = (string) ($this->db->get_var(
            "SELECT title FROM {$this->prefix}posts WHERE id = ? LIMIT 1",
            [$postId]
        ) ?? ('Post #' . $postId));

        $subject = 'Neuer Kommentar wartet auf Freigabe';
        $body = '<p>Ein neuer Kommentar wurde eingereicht und wartet auf Moderation.</p>'
            . '<p><strong>Beitrag:</strong> ' . htmlspecialchars($postTitle, ENT_QUOTES) . '<br>'
            . '<strong>Autor:</strong> ' . htmlspecialchars($author, ENT_QUOTES) . '<br>'
            . '<strong>E-Mail:</strong> ' . htmlspecialchars($email, ENT_QUOTES) . '</p>'
            . '<p><a href="' . htmlspecialchars((string) SITE_URL, ENT_QUOTES) . '/admin/comments">➡️ Zur Moderation</a></p>';

        $headers = [
            'X-365CMS-Test-Source' => 'comments-pending-moderation',
        ];

        if (MailQueueService::getInstance()->shouldQueue($headers)) {
            MailQueueService::getInstance()->enqueue(
                $to,
                $subject,
                $body,
                $headers,
                null,
                'comments-pending-moderation'
            );
            return;
        }

        MailService::getInstance()->send($to, $subject, $body, $headers);
    }
}
