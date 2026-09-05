<?php
Security::requireAdmin();

$pageTitle  = 'Accounts — Expenses';
$activePage = 'accounts';

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$fType  = $_GET['type']  ?? '';
$fMonth = (int)($_GET['month'] ?? 0);
$fYear  = (int)($_GET['year']  ?? 0);

$typeLabels = ['marketing' => 'Marketing', 'general' => 'General', 'salary' => 'Salary'];
$typeColors = ['marketing' => '#3b82f6', 'general' => '#8b5cf6', 'salary' => '#f59e0b'];

ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= Security::e($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-error">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
    <?= Security::e($_SESSION['error']) ?></div>
<?php unset($_SESSION['error']); endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Expenses</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/accounts">Accounts</a> <span class="sep">/</span> <span class="current">Expenses</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addExpModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Expense
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/accounts',            'Overview',   false],
        [APP_URL.'/admin/accounts/commission', 'Commission', false],
        [APP_URL.'/admin/accounts/expenses',   'Expenses',   true],
        [APP_URL.'/admin/accounts/salaries',   'Salaries',   false],
    ] as [$url, $label, $active]): ?>
    <a href="<?= $url ?>" style="
        padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;
        text-decoration:none;border:1px solid var(--border);border-bottom:none;margin-bottom:-2px;
        background:<?= $active ? 'var(--bg-card)' : 'transparent' ?>;
        color:<?= $active ? 'var(--gold)' : 'var(--text-muted)' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Stat chips -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <?php foreach ([
        ['Total',     $pkr($stats['total']    ?? 0), '#6366f1', ''],
        ['Marketing', $pkr($stats['marketing'] ?? 0), '#3b82f6', '?type=marketing'],
        ['Salary',    $pkr($stats['salary']   ?? 0), '#f59e0b', '?type=salary'],
        ['General',   $pkr($stats['general']  ?? 0), '#8b5cf6', '?type=general'],
    ] as [$label, $amount, $color, $qs]): ?>
    <a href="<?= APP_URL . '/admin/accounts/expenses' . $qs ?>" style="text-decoration:none">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
            <span style="font-size:15px;font-weight:700;color:<?= $color ?>"><?= $amount ?></span>
            <span style="font-size:12px;color:var(--text-muted);margin-left:6px"><?= $label ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Type filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px">
    <?php foreach (['' => 'All', 'marketing' => 'Marketing', 'general' => 'General'] as $val => $label): ?>
    <a href="<?= APP_URL ?>/admin/accounts/expenses?type=<?= $val ?><?= $fMonth ? '&month='.$fMonth : '' ?><?= $fYear ? '&year='.$fYear : '' ?>"
       style="padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;
              background:<?= $fType === $val ? 'var(--navy)' : 'var(--bg-card)' ?>;
              color:<?= $fType === $val ? '#fff' : 'var(--text-muted)' ?>;
              border:1px solid <?= $fType === $val ? 'var(--navy)' : 'var(--border)' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Date filter -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/accounts/expenses">
        <?php if ($fType): ?><input type="hidden" name="type" value="<?= Security::e($fType) ?>"><?php endif; ?>
        <div class="filter-row">
            <select name="month" class="filter-input">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $fMonth === $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" value="<?= $fYear ?: '' ?>" placeholder="Year" class="filter-input" style="width:90px">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/accounts/expenses<?= $fType ? '?type='.$fType : '' ?>" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($expenses)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No expenses found.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:700px">
    <thead>
        <tr>
            <th>Type</th>
            <th>Category</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Added By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($expenses as $e): ?>
    <tr>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:<?= $e['type'] === 'marketing' ? 'rgba(59,130,246,.12)' : ($e['type'] === 'general' ? 'rgba(139,92,246,.12)' : 'rgba(245,158,11,.12)') ?>;
                color:<?= $typeColors[$e['type']] ?? '#6b7280' ?>">
                <?= $typeLabels[$e['type']] ?? ucfirst($e['type']) ?>
            </span>
        </td>
        <td style="font-weight:600"><?= Security::e($e['category']) ?></td>
        <td style="color:var(--text-muted);max-width:220px"><?= Security::e($e['description'] ?? '-') ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($e['amount']) ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars(date('d M Y', strtotime($e['expense_date']))) ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($e['created_by_name'] ?? '-') ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editExp(<?= json_encode($e) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="expense_id"  value="<?= (int)$e['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this expense?')">Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addExpModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Add Expense</h3>
            <button class="modal-close" onclick="closeModal('addExpModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-input" required>
                            <option value="marketing">Marketing</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <input type="text" name="category" class="form-input" placeholder="e.g. Facebook Ads" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (PKR) *</label>
                        <input type="number" step="1" min="0" name="amount" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="expense_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addExpModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editExpModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Edit Expense</h3>
            <button class="modal-close" onclick="closeModal('editExpModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses" id="editExpForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="expense_id"  id="editExpId">
            <div class="modal-body" id="editExpBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editExpModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editExp(data) {
    document.getElementById('editExpId').value = data.id;
    const src = document.querySelector('#addExpModal .modal-body');
    const dst = document.getElementById('editExpBody');
    dst.innerHTML = src.innerHTML;
    const fields = { type: data.type, category: data.category, amount: data.amount,
                     expense_date: data.expense_date, description: data.description || '' };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (opt.value === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editExpModal');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
