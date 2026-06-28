<?php
Security::requireAdmin();
// $detail, $agentLeads, $months injected by AdminController::agentDetail()
$agent  = $detail;
$stats  = $agent['stats'];
$month  = $_GET['month'] ?? '';

$pageTitle  = 'Agent: ' . ($agent['name'] ?? 'Detail');
$activePage = 'agents';
ob_start();

$total      = (int)$stats['total_leads'];
$won        = (int)$stats['st_won'];
$lost       = (int)$stats['st_lost'];
$closed     = $won + $lost;
$conversion = $closed > 0 ? round(($won / $closed) * 100) : 0;
$initials   = strtoupper(substr($agent['name'] ?? 'A', 0, 1));
$isSM       = $agent['role'] === 'sales_manager';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($agent['name'] ?? 'Agent') ?></h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <a href="<?= APP_URL ?>/admin/agents">Agents</a>
            <span class="sep">/</span>
            <span class="current"><?= Security::e($agent['name'] ?? 'Detail') ?></span>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
    </div>
</div>

<!-- Month Filter -->
<form method="GET" action="<?= APP_URL ?>/admin/agent/<?= (int)$agent['id'] ?>" style="margin-bottom:20px;display:flex;align-items:center;gap:12px">
    <label style="font-size:13px;font-weight:500;color:var(--text-muted)">Filter by month:</label>
    <select name="month" onchange="this.form.submit()" style="font-size:13px;padding:7px 12px;border:1px solid var(--border);border-radius:8px;background:#fff">
        <option value="">All Time</option>
        <?php foreach ($months as $m): ?>
            <option value="<?= Security::e($m['ym']) ?>" <?= $month === $m['ym'] ? 'selected' : '' ?>>
                <?= Security::e($m['label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($month): ?>
        <a href="<?= APP_URL ?>/admin/agent/<?= (int)$agent['id'] ?>" style="font-size:13px;color:var(--text-muted)">Clear</a>
    <?php endif; ?>
</form>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start">

    <!-- Profile Card -->
    <div class="card">
        <div style="text-align:center;padding:20px 0 14px">
            <div class="sidebar-avatar" style="width:70px;height:70px;font-size:28px;margin:0 auto 12px;<?= $isSM ? 'background:var(--gold);color:var(--navy)' : '' ?>">
                <?= Security::e($initials) ?>
            </div>
            <div style="font-size:18px;font-weight:600;color:var(--navy)"><?= Security::e($agent['name'] ?? '—') ?></div>
            <div style="margin-top:6px">
                <?php if ($isSM): ?>
                    <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Sales Manager</span>
                <?php else: ?>
                    <span class="status-pill" style="background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fa">Agent</span>
                <?php endif; ?>
                <?php if ($agent['status'] === 'suspended'): ?>
                    <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;margin-left:4px">Suspended</span>
                <?php endif; ?>
            </div>
            <?php if ($agent['designation']): ?>
                <div style="font-size:13px;color:var(--text-muted);margin-top:8px"><?= Security::e($agent['designation']) ?></div>
            <?php endif; ?>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:0 0 16px">
        <div style="display:flex;flex-direction:column;gap:12px;padding:0 4px 8px">
            <?php $rows = [
                ['Email',    $agent['email']          ?? null],
                ['Phone',    $agent['phone']          ?? null],
                ['CNIC',     $agent['cnic']           ?? null],
                ['Guardian', $agent['guardian_phone'] ?? null],
                ['Address',  $agent['address']        ?? null],
            ]; ?>
            <?php foreach ($rows as [$label, $value]): ?>
                <?php if ($value): ?>
                <div style="display:flex;gap:10px;font-size:13px">
                    <span style="color:var(--text-muted);width:70px;flex-shrink:0"><?= $label ?></span>
                    <span style="color:var(--navy);font-weight:500;word-break:break-word"><?= Security::e($value) ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div style="display:flex;gap:10px;font-size:13px">
                <span style="color:var(--text-muted);width:70px;flex-shrink:0">Joined</span>
                <span style="color:var(--navy);font-weight:500"><?= date('d M Y', strtotime($agent['created_at'])) ?></span>
            </div>
            <div style="display:flex;gap:10px;font-size:13px">
                <span style="color:var(--text-muted);width:70px;flex-shrink:0">Salary</span>
                <span style="color:var(--navy);font-weight:500">Rs. <?= number_format((float)$agent['base_salary']) ?>/mo</span>
            </div>
            <div style="display:flex;gap:10px;font-size:13px">
                <span style="color:var(--text-muted);width:70px;flex-shrink:0">Commission</span>
                <span style="color:var(--navy);font-weight:500"><?= number_format((float)$agent['commission_rate'], 1) ?>% of booking</span>
            </div>
        </div>
    </div>

    <!-- Stats + Leads -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Leads</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Won / Closed</div>
                <div class="stat-value" style="color:#10b981"><?= $won ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lost / Dead</div>
                <div class="stat-value" style="color:#ef4444"><?= (int)$stats['not_interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Pipeline</div>
                <div class="stat-value" style="color:#f59e0b"><?= (int)$stats['interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Conversion</div>
                <div class="stat-value"><?= $conversion ?>%</div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="card">
            <div class="card-header" style="font-size:14px;font-weight:600;color:var(--navy);padding:14px 18px 12px;border-bottom:1px solid var(--border)">
                Status Breakdown
            </div>
            <div style="padding:14px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px">
                <?php
                $statusRows = [
                    ['New',         $stats['st_new'],         '#3B82F6'],
                    ['Contacted',   $stats['st_contacted'],   '#8B5CF6'],
                    ['Qualified',   $stats['st_qualified'],   '#F59E0B'],
                    ['Proposal',    $stats['st_proposal'],    '#EC4899'],
                    ['Negotiation', $stats['st_negotiation'], '#F97316'],
                    ['Won',         $stats['st_won'],         '#10B981'],
                    ['Lost',        $stats['st_lost'],        '#EF4444'],
                    ['Dead',        $stats['st_dead'],        '#6B7280'],
                ];
                foreach ($statusRows as [$label, $count, $color]):
                ?>
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:12px 14px;border-left:3px solid <?= $color ?>">
                    <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$count ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">
                Leads<?= $month ? ' — ' . Security::e($month) : '' ?> (<?= count($agentLeads) ?>)
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name / Phone</th>
                            <th>Project</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($agentLeads as $row): ?>
                        <tr>
                            <td>
                                <div style="font-weight:500;color:var(--navy)"><?= Security::e($row['name']) ?></div>
                                <?php if ($row['phone']): ?>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($row['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px"><?= $row['project'] ? Security::e($row['project']) : '—' ?></td>
                            <td style="font-size:13px"><?= $row['category'] ? Security::e($row['category']) : '—' ?></td>
                            <td>
                                <span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44">
                                    <?= Security::e($row['status_name']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="<?= APP_URL ?>/admin/lead/<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($agentLeads)): ?>
                        <tr><td colspan="6" class="empty-row">No leads<?= $month ? ' for this month' : '' ?>.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /right col -->
</div>

<style>
@media print {
    .sidebar, .page-header-actions, .breadcrumb, form[method="GET"], .btn, thead { }
    .sidebar, .hamburger-btn, .sidebar-overlay { display:none !important }
    .main-content { margin:0 !important; padding:20px !important }
    .page-header-actions { display:none !important }
    body::before {
        content: "HK Builders & Developers — Agent Report — <?= date('d M Y') ?>";
        display:block; font-size:13px; color:#666; margin-bottom:20px;
        border-bottom:2px solid #c9a84c; padding-bottom:10px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
