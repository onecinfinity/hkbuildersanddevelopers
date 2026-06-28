<?php
Security::requireLogin();
// $lead, $sources injected by AgentController::convertClient()
$pageTitle  = 'Convert to Client';
$activePage = 'leads';
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Convert to Client</h1>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/agent/leads" style="color:var(--text-muted)">My Leads</a>
            <span class="sep">/</span>
            <a href="<?= APP_URL ?>/agent/lead/<?= (int)$lead['id'] ?>" style="color:var(--text-muted)"><?= Security::e($lead['display_name']) ?></a>
            <span class="sep">/</span>
            <span class="current">Convert to Client</span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/agent/lead/<?= (int)$lead['id'] ?>" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to Lead
        </a>
    </div>
</div>

<!-- Won Banner -->
<div style="background:linear-gradient(135deg,#0a1628,#13233d);border-radius:14px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;gap:16px">
    <div style="width:46px;height:46px;background:rgba(16,185,129,.15);border:2px solid #10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#10b981" style="width:22px;height:22px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:#fff">Deal Closed — <?= Security::e($lead['display_name']) ?></div>
        <div style="font-size:13px;color:#9fadc4;margin-top:2px">Fill in the booking details below. This page stays open — take your time, it won't be lost on refresh.</div>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/agent/lead/<?= (int)$lead['id'] ?>/convert">
    <?= Security::csrfField() ?>

    <!-- Client Info -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <h3>Client Information</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= Security::e($lead['name'] ?? '') ?>" placeholder="Client full name">
                </div>
                <div class="form-group">
                    <label>Contact No.</label>
                    <input type="text" name="contact_no" value="<?= Security::e($lead['phone'] ?? '') ?>" placeholder="03XX-XXXXXXX">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= Security::e($lead['address'] ?? '') ?>" placeholder="Client home address">
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            <h3>Booking Details</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label>Project</label>
                    <input type="text" name="project" value="<?= Security::e($lead['project'] ?? '') ?>" placeholder="e.g. Falaknaz Hills View">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">— Select —</option>
                        <?php foreach (['Residential','Commercial','Plot','Other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($lead['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Block</label>
                    <input type="text" name="block" placeholder="e.g. Block A, Sector 5">
                </div>
                <div class="form-group">
                    <label>Unit No.</label>
                    <input type="text" name="unit_no" value="<?= Security::e($lead['unit'] ?? '') ?>" placeholder="e.g. Flat 302, Plot 45">
                </div>
                <div class="form-group">
                    <label>Booking Amount (Rs.)</label>
                    <input type="number" name="booking_amount" value="<?= Security::e($lead['investment_amount'] ?? '') ?>" placeholder="e.g. 500000" min="0" step="1000">
                </div>
            </div>
        </div>
    </div>

    <!-- File Status -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <h3>File Status</h3>
        </div>
        <div class="form-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <label style="display:flex;align-items:flex-start;gap:12px;padding:16px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all .2s" id="label-mature">
                    <input type="radio" name="file_status" value="mature" checked onchange="toggleFlagReason()" style="margin-top:3px;accent-color:#10b981">
                    <div>
                        <div style="font-weight:600;color:#10b981">Mature File</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:3px">Client paid the full agreed booking amount</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;padding:16px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all .2s" id="label-immature">
                    <input type="radio" name="file_status" value="immature" onchange="toggleFlagReason()" style="margin-top:3px;accent-color:#f59e0b">
                    <div>
                        <div style="font-weight:600;color:#f59e0b">Immature File</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:3px">Booking closed below agreed amount — commission pending</div>
                    </div>
                </label>
            </div>
            <div id="flagReasonBox" style="display:none;margin-top:14px">
                <div class="form-group">
                    <label>Reason file is immature</label>
                    <select name="flag_reason">
                        <option value="no_funds">Client doesn't have funds yet</option>
                        <option value="partial_payment">Partial payment received</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="<?= APP_URL ?>/agent/lead/<?= (int)$lead['id'] ?>" class="btn btn-secondary">Fill Later</a>
        <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Save Client Record
        </button>
    </div>
</form>

<script>
function toggleFlagReason() {
    const isImmature = document.querySelector('input[name="file_status"]:checked').value === 'immature';
    document.getElementById('flagReasonBox').style.display = isImmature ? 'block' : 'none';
    document.getElementById('label-mature').style.borderColor   = !isImmature ? '#10b981' : 'var(--border)';
    document.getElementById('label-immature').style.borderColor = isImmature  ? '#f59e0b' : 'var(--border)';
}
toggleFlagReason();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php';
?>
