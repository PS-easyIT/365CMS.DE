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

use CMS\AuditLogger;
use CMS\Database;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class CommentService
{
    private const MAX_AUTHOR_LENGTH = 100;
    private const MAX_EMAIL_LENGTH = 150;
    private const MAX_CONTENT_LENGTH = 5000;
    private const MAX_LIST_LIMIT = 200;
    private const FLOOD_WINDOW_MINUTES = 15;
    private const MAX_COMMENTS_PER_WINDOW = 5;

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
        if ($postId <= 0) {
            return [];
        }

        return $this->db->get_results(
            "SELECT id, user_id, author, content, post_date,
                    CASE WHEN user_id IS NOT NULL AND author = 'Anonym' THEN 1 ELSE 0 END AS is_anonymous
             FROM {$this->prefix}comments
             WHERE post_id = ? AND status = 'approved'
             ORDER BY post_date ASC",
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
        ?int $userId = null,
        bool $isAnonymous = false
    ): int|false {
        [$resolvedAuthorName, $resolvedAuthorEmail] = $this->resolveCommentAuthorIdentity($userId, $authorName, $authorEmail);

        if (($userId ?? 0) > 0 && $isAnonymous) {
            $resolvedAuthorName = 'Anonym';
        }

        $authorName = $this->sanitizeAuthorName($resolvedAuthorName);

        $authorEmail = mb_substr(trim($resolvedAuthorEmail), 0, self::MAX_EMAIL_LENGTH);
        $validatedEmail = filter_var($authorEmail, FILTER_VALIDATE_EMAIL);

        $cleanContent = $this->sanitizeCommentContent($content);
        $normalizedIp = $this->normalizeIpAddress($authorIp);

        if ($postId <= 0 || $authorName === '' || $validatedEmail === false || $cleanContent === '') {
            $this->logFailure('comments.create.invalid_payload', 'Kommentar mit ungültigen Eingaben verworfen.', [
                'post_id' => $postId,
                'user_id' => $userId,
                'has_author' => $authorName !== '',
                'has_valid_email' => $validatedEmail !== false,
                'has_content' => $cleanContent !== '',
            ]);
            return false;
        }

        if (!$this->isCommentablePost($postId)) {
            $this->logFailure('comments.create.post_not_commentable', 'Kommentar für nicht kommentierbaren Beitrag verworfen.', [
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            return false;
        }

        if ($this->isRateLimited((string) $validatedEmail, $normalizedIp, $userId)) {
            $this->logFailure('comments.create.rate_limited', 'Kommentar wegen Kommentar-Flood-Limit verworfen.', [
                'post_id' => $postId,
                'user_id' => $userId,
                'email_hash' => sha1(strtolower((string) $validatedEmail)),
                'ip' => $normalizedIp,
            ]);
            return false;
        }

        $insertId = $this->db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'author' => $authorName,
            'author_email' => (string) $validatedEmail,
            'author_ip' => $normalizedIp,
            'content' => $cleanContent,
            'status' => 'pending',
        ]);

        if ($insertId === false) {
            $this->logFailure('comments.create.persist_failed', 'Kommentar konnte nicht gespeichert werden.', [
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            return false;
        }

        $this->logSuccess('comments.create.pending', 'Kommentar gespeichert und zur Moderation vorgemerkt.', [
            'comment_id' => $insertId,
            'post_id' => $postId,
            'user_id' => $userId,
            'is_anonymous' => $isAnonymous,
        ], $insertId);

        $this->notifyAdminForPendingComment($postId, $authorName, (string) $validatedEmail);

        return $insertId;
    }

    /**
     * Nutzt bei eingeloggten Nutzern immer das hinterlegte Profil statt frei gesendeter Formularwerte.
     *
     * @return array{0:string,1:string}
     */
    private function resolveCommentAuthorIdentity(?int $userId, string $fallbackName, string $fallbackEmail): array
    {
        $fallbackName = trim($fallbackName);
        $fallbackEmail = trim($fallbackEmail);

        if (($userId ?? 0) <= 0) {
            return [$fallbackName, $fallbackEmail];
        }

        $user = $this->db->get_row(
            "SELECT username, email, display_name
             FROM {$this->prefix}users
             WHERE id = ?
             LIMIT 1",
            [(int) $userId]
        );

        if (!$user) {
            return [$fallbackName, $fallbackEmail];
        }

        $resolvedName = trim((string) ($user->display_name ?? ''));
        if ($resolvedName === '') {
            $resolvedName = trim((string) ($user->username ?? ''));
        }
        if ($resolvedName === '') {
            $resolvedName = $fallbackName;
        }

        $resolvedEmail = trim((string) ($user->email ?? ''));
        if ($resolvedEmail === '') {
            $resolvedEmail = $fallbackEmail;
        }

        return [$resolvedName, $resolvedEmail];
    }

    public function getComments(string $status = 'all', int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(self::MAX_LIST_LIMIT, $limit));
        $offset = max(0, $offset);

        $where = '';
        $params = [];

        if (in_array($status, ['pending', 'approved', 'spam', 'trash'], true)) {
            $where = 'WHERE c.status = ?';
            $params[] = $status;
        } elseif ($status === 'all') {
            $where = "WHERE c.status <> 'spam'";
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->get_results(
            "SELECT c.id, c.post_id, c.author, c.author_email, c.content, c.status, c.post_date,
                p.title AS post_title, p.slug AS post_slug,
                p.published_at AS post_published_at, p.created_at AS post_created_at
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
            if ($status !== 'spam') {
                $counts['all'] += $cnt;
            }
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

    private function sanitizeAuthorName(string $authorName): string
    {
        $authorName = trim(strip_tags($authorName));
        $authorName = preg_replace('/\s+/u', ' ', $authorName) ?? '';

        return mb_substr($authorName, 0, self::MAX_AUTHOR_LENGTH);
    }

    private function sanitizeCommentContent(string $content): string
    {
        $cleanContent = trim(PurifierService::getInstance()->purify($content, 'strict'));
        if ($cleanContent === '') {
            return '';
        }

        if (mb_strlen($cleanContent) > self::MAX_CONTENT_LENGTH) {
            return '';
        }

        return $cleanContent;
    }

    private function normalizeIpAddress(string $authorIp): string
    {
        $authorIp = trim($authorIp);
        if ($authorIp === '') {
            return '';
        }

        return filter_var($authorIp, FILTER_VALIDATE_IP) ? mb_substr($authorIp, 0, 45) : '';
    }

    private function isCommentablePost(int $postId): bool
    {
        $row = $this->db->get_row(
            "SELECT id, status, allow_comments
             FROM {$this->prefix}posts
             WHERE id = ?
             LIMIT 1",
            [$postId]
        );

        if (!$row) {
            return false;
        }

        return (string) ($row->status ?? '') === 'published'
            && (int) ($row->allow_comments ?? 0) === 1;
    }

    private function isRateLimited(string $email, string $ipAddress, ?int $userId): bool
    {
        $conditions = [];
        $params = [];

        if ($ipAddress !== '') {
            $conditions[] = 'author_ip = ?';
            $params[] = $ipAddress;
        }

        $conditions[] = 'author_email = ?';
        $params[] = $email;

        if (($userId ?? 0) > 0) {
            $conditions[] = 'user_id = ?';
            $params[] = (int) $userId;
        }

        if ($conditions === []) {
            return false;
        }

        $query = implode(' OR ', $conditions);
        $count = (int) ($this->db->get_var(
            "SELECT COUNT(*)
             FROM {$this->prefix}comments
             WHERE ({$query})
               AND post_date >= (NOW() - INTERVAL " . self::FLOOD_WINDOW_MINUTES . " MINUTE)",
            $params
        ) ?? 0);

        return $count >= self::MAX_COMMENTS_PER_WINDOW;
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

    private function logFailure(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('comments')->warning($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'comment',
            isset($context['comment_id']) ? (int) $context['comment_id'] : null,
            $context,
            'warning'
        );
    }

    private function logSuccess(string $action, string $message, array $context = [], ?int $commentId = null): void
    {
        Logger::instance()->withChannel('comments')->info($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'comment',
            $commentId,
            $context,
            'info'
        );
    }
}
