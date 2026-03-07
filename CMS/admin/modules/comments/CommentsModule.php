<?php
declare(strict_types=1);

/**
 * Comments Module – Moderationslogik für Kommentare
 *
 * Nutzt CMS\Services\CommentService für CRUD.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\CommentService;

class CommentsModule
{
    private CommentService $service;

    public function __construct()
    {
        $this->service = CommentService::getInstance();
    }

    /**
     * Daten für die Listenansicht
     */
    public function getListData(): array
    {
        $counts = $this->service->getCounts();
        $status = $_GET['status'] ?? 'all';

        if (!in_array($status, ['all', 'pending', 'approved', 'spam', 'trash'], true)) {
            $status = 'all';
        }

        $comments = $this->service->getComments($status, 200, 0);

        return [
            'comments' => $comments,
            'counts'   => $counts,
            'status'   => $status,
        ];
    }

    /**
     * Status eines Kommentars ändern
     */
    public function updateStatus(int $id, string $status): array
    {
        if ($this->service->updateStatus($id, $status)) {
            $labels = [
                'approved' => 'Kommentar freigegeben.',
                'pending'  => 'Kommentar in Warteschlange verschoben.',
                'spam'     => 'Kommentar als Spam markiert.',
                'trash'    => 'Kommentar in den Papierkorb verschoben.',
            ];
            return ['success' => true, 'message' => $labels[$status] ?? 'Status aktualisiert.'];
        }
        return ['success' => false, 'error' => 'Fehler beim Ändern des Status.'];
    }

    /**
     * Kommentar endgültig löschen
     */
    public function delete(int $id): array
    {
        if ($this->service->delete($id)) {
            return ['success' => true, 'message' => 'Kommentar gelöscht.'];
        }
        return ['success' => false, 'error' => 'Fehler beim Löschen.'];
    }

    /**
     * Bulk-Aktion
     */
    public function bulkAction(string $action, array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'error' => 'Keine Einträge ausgewählt.'];
        }

        $count   = 0;
        $success = true;

        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;

            switch ($action) {
                case 'approve':
                    $success = $this->service->updateStatus($id, 'approved') && $success;
                    break;
                case 'spam':
                    $success = $this->service->updateStatus($id, 'spam') && $success;
                    break;
                case 'trash':
                    $success = $this->service->updateStatus($id, 'trash') && $success;
                    break;
                case 'delete':
                    $success = $this->service->delete($id) && $success;
                    break;
                default:
                    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
            }
            $count++;
        }

        $labels = [
            'approve' => 'freigegeben',
            'spam'    => 'als Spam markiert',
            'trash'   => 'in Papierkorb verschoben',
            'delete'  => 'gelöscht',
        ];

        return [
            'success' => $success,
            'message' => $count . ' Kommentar(e) ' . ($labels[$action] ?? 'bearbeitet') . '.',
        ];
    }
}
