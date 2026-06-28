<?php
require_once __DIR__ . '/../../config/database.php';

class Client {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByLeadId(int $leadId): array|false {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.name AS agent_name, src.name AS source_name
             FROM clients c
             LEFT JOIN users u ON u.id = c.agent_id
             LEFT JOIN lead_sources src ON src.id = c.source_id
             WHERE c.lead_id = ? LIMIT 1'
        );
        $stmt->execute([$leadId]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.name AS agent_name, src.name AS source_name
             FROM clients c
             LEFT JOIN users u ON u.id = c.agent_id
             LEFT JOIN lead_sources src ON src.id = c.source_id
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data, int $createdBy): int {
        $stmt = $this->db->prepare("
            INSERT INTO clients
                (lead_id, name, address, contact_no, project, block, unit_no,
                 category, booking_amount, agent_id, source_id,
                 file_status, file_flag_reason, flagged_by, flagged_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $isImmature  = ($data['file_status'] ?? 'mature') === 'immature';
        $flaggedBy   = $isImmature ? $createdBy : null;
        $flaggedAt   = $isImmature ? date('Y-m-d H:i:s') : null;

        $stmt->execute([
            $data['lead_id']          ?? null,
            ($data['name'] ?? '')     !== '' ? $data['name']    : null,
            ($data['address'] ?? '')  !== '' ? $data['address'] : null,
            ($data['contact_no'] ?? '') !== '' ? $data['contact_no'] : null,
            ($data['project'] ?? '')  !== '' ? $data['project'] : null,
            ($data['block'] ?? '')    !== '' ? $data['block']   : null,
            ($data['unit_no'] ?? '')  !== '' ? $data['unit_no'] : null,
            ($data['category'] ?? '') !== '' ? $data['category'] : null,
            !empty($data['booking_amount']) ? (float)$data['booking_amount'] : null,
            $data['agent_id']         ?? null,
            $data['source_id']        ?? null,
            $isImmature ? 'immature' : 'mature',
            $isImmature && !empty($data['flag_reason']) ? $data['flag_reason'] : null,
            $flaggedBy,
            $flaggedAt,
            $createdBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $updatedBy): void {
        $isImmature = ($data['file_status'] ?? 'mature') === 'immature';
        $stmt = $this->db->prepare("
            UPDATE clients SET
                name=?, address=?, contact_no=?, project=?, block=?, unit_no=?,
                category=?, booking_amount=?, source_id=?,
                file_status=?, file_flag_reason=?,
                flagged_by = CASE WHEN ? = 'immature' AND file_status != 'immature' THEN ? ELSE flagged_by END,
                flagged_at = CASE WHEN ? = 'immature' AND file_status != 'immature' THEN NOW()   ELSE flagged_at END
            WHERE id = ?
        ");
        $stmt->execute([
            ($data['name'] ?? '')        !== '' ? $data['name']       : null,
            ($data['address'] ?? '')     !== '' ? $data['address']    : null,
            ($data['contact_no'] ?? '')  !== '' ? $data['contact_no'] : null,
            ($data['project'] ?? '')     !== '' ? $data['project']    : null,
            ($data['block'] ?? '')       !== '' ? $data['block']      : null,
            ($data['unit_no'] ?? '')     !== '' ? $data['unit_no']    : null,
            ($data['category'] ?? '')    !== '' ? $data['category']   : null,
            !empty($data['booking_amount']) ? (float)$data['booking_amount'] : null,
            !empty($data['source_id'])   ? (int)$data['source_id']   : null,
            $isImmature ? 'immature' : 'mature',
            $isImmature && !empty($data['flag_reason']) ? $data['flag_reason'] : null,
            $isImmature ? 'immature' : 'mature', $updatedBy,
            $isImmature ? 'immature' : 'mature',
            $id,
        ]);
    }

    public function getAll(array $opts = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($opts['agent_id'])) {
            $where[] = 'c.agent_id = ?';
            $params[] = (int)$opts['agent_id'];
        }
        if (!empty($opts['file_status'])) {
            $where[] = 'c.file_status = ?';
            $params[] = $opts['file_status'];
        }
        if (!empty($opts['project'])) {
            $where[] = 'c.project LIKE ?';
            $params[] = '%' . $opts['project'] . '%';
        }
        if (!empty($opts['search'])) {
            $s = '%' . $opts['search'] . '%';
            $where[] = '(c.name LIKE ? OR c.contact_no LIKE ? OR c.project LIKE ? OR c.unit_no LIKE ?)';
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $limit  = (int)($opts['limit']  ?? 50);
        $offset = (int)($opts['offset'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS agent_name, src.name AS source_name
            FROM clients c
            LEFT JOIN users u       ON u.id   = c.agent_id
            LEFT JOIN lead_sources src ON src.id = c.source_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $opts = []): int {
        $where  = ['1=1'];
        $params = [];
        if (!empty($opts['agent_id']))   { $where[] = 'c.agent_id = ?';    $params[] = (int)$opts['agent_id']; }
        if (!empty($opts['file_status'])){ $where[] = 'c.file_status = ?'; $params[] = $opts['file_status']; }
        if (!empty($opts['search'])) {
            $s = '%' . $opts['search'] . '%';
            $where[] = '(c.name LIKE ? OR c.contact_no LIKE ? OR c.project LIKE ?)';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM clients c WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getSummaryStats(): array {
        $row = $this->db->query("
            SELECT COUNT(*) AS total,
                   SUM(file_status = 'mature')   AS mature,
                   SUM(file_status = 'immature') AS immature,
                   COALESCE(SUM(booking_amount), 0) AS total_booking
            FROM clients
        ")->fetch();
        return $row ?: ['total'=>0,'mature'=>0,'immature'=>0,'total_booking'=>0];
    }
}
