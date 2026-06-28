<?php
require_once __DIR__ . '/../../config/database.php';

class Team {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll(): array {
        return $this->db->query("
            SELECT t.*,
                   sm.name  AS manager_name,
                   sm.email AS manager_email,
                   COUNT(DISTINCT u.id) AS member_count,
                   COUNT(DISTINCT l.id) AS total_leads,
                   SUM(ls.name = 'Won')  AS won,
                   SUM(ls.name = 'Lost') AS lost
            FROM teams t
            LEFT JOIN users u  ON u.team_id = t.id AND u.role = 'agent'
            LEFT JOIN users sm ON sm.id = t.sales_manager_id
            LEFT JOIN leads l  ON l.assigned_to = u.id AND l.deleted_at IS NULL
            LEFT JOIN lead_statuses ls ON ls.id = l.status_id
            GROUP BY t.id
            ORDER BY t.name
        ")->fetchAll();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT t.*, sm.name AS manager_name, sm.email AS manager_email
            FROM teams t
            LEFT JOIN users sm ON sm.id = t.sales_manager_id
            WHERE t.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getMembers(int $teamId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email, u.designation, u.status, u.phone,
                   COUNT(DISTINCT l.id)      AS total_leads,
                   SUM(ls.name = 'Won')       AS won,
                   SUM(ls.name = 'Lost')      AS lost,
                   SUM(ls.name IN ('Qualified','Proposal','Negotiation')) AS active
            FROM users u
            LEFT JOIN leads l  ON l.assigned_to = u.id AND l.deleted_at IS NULL
            LEFT JOIN lead_statuses ls ON ls.id = l.status_id
            WHERE u.team_id = ? AND u.role = 'agent'
            GROUP BY u.id
            ORDER BY u.name
        ");
        $stmt->execute([$teamId]);
        return $stmt->fetchAll();
    }

    public function getTeamStats(int $teamId, string $month = ''): array {
        $params     = [$teamId];
        $monthWhere = '';
        if ($month) {
            $monthWhere = "AND DATE_FORMAT(l.created_at,'%Y-%m') = ?";
            $params[]   = $month;
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT l.id)                                       AS total_leads,
                SUM(ls.name = 'New')                                       AS st_new,
                SUM(ls.name = 'Contacted')                                 AS st_contacted,
                SUM(ls.name = 'Qualified')                                 AS st_qualified,
                SUM(ls.name = 'Proposal')                                  AS st_proposal,
                SUM(ls.name = 'Negotiation')                               AS st_negotiation,
                SUM(ls.name = 'Won')                                       AS st_won,
                SUM(ls.name = 'Lost')                                      AS st_lost,
                SUM(ls.name = 'Dead')                                      AS st_dead,
                SUM(ls.name IN ('Qualified','Proposal','Negotiation'))     AS interested,
                SUM(ls.name IN ('Lost','Dead'))                            AS not_interested,
                SUM(c.id IS NOT NULL)                                      AS clients,
                COALESCE(SUM(c.booking_amount),0)                          AS total_booking
            FROM users u
            LEFT JOIN leads l  ON l.assigned_to = u.id AND l.deleted_at IS NULL $monthWhere
            LEFT JOIN lead_statuses ls ON ls.id = l.status_id
            LEFT JOIN clients c ON c.lead_id = l.id
            WHERE u.team_id = ? AND u.role = 'agent'
        ");
        // month param comes before teamId in query
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    public function create(string $name, ?int $managerId): int {
        $stmt = $this->db->prepare(
            'INSERT INTO teams (name, sales_manager_id) VALUES (?, ?)'
        );
        $stmt->execute([$name, $managerId ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?int $managerId): void {
        $this->db->prepare(
            'UPDATE teams SET name = ?, sales_manager_id = ? WHERE id = ?'
        )->execute([$name, $managerId ?: null, $id]);
    }

    public function delete(int $id): void {
        // Unassign all agents from this team first
        $this->db->prepare('UPDATE users SET team_id = NULL WHERE team_id = ?')
                 ->execute([$id]);
        $this->db->prepare('DELETE FROM teams WHERE id = ?')->execute([$id]);
    }

    public function assignAgent(int $agentId, ?int $teamId): void {
        $this->db->prepare('UPDATE users SET team_id = ? WHERE id = ?')
                 ->execute([$teamId ?: null, $agentId]);
    }

    public function getSalesManagers(): array {
        return $this->db->query("
            SELECT id, name, email, designation FROM users
            WHERE role = 'sales_manager' AND status = 'active'
            ORDER BY name
        ")->fetchAll();
    }

    public function getUnassignedAgents(): array {
        return $this->db->query("
            SELECT id, name, email, designation FROM users
            WHERE role = 'agent' AND team_id IS NULL AND status = 'active'
            ORDER BY name
        ")->fetchAll();
    }

    public function getManagerTeam(int $managerId): array|false {
        $stmt = $this->db->prepare(
            'SELECT * FROM teams WHERE sales_manager_id = ? LIMIT 1'
        );
        $stmt->execute([$managerId]);
        return $stmt->fetch();
    }
}
