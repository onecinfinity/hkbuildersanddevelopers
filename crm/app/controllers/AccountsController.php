<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/AuditLog.php';
require_once __DIR__ . '/../models/Accounts.php';

class AccountsController {

    private Accounts $accounts;

    public function __construct() {
        $this->accounts = new Accounts();
    }

    public function accounts(?string $sub): void {
        match($sub ?: 'overview') {
            'commission' => $this->commission(),
            'expenses'   => $this->expenses(),
            'salaries'   => $this->salaries(),
            default      => $this->overview(),
        };
    }

    // ---- Overview --------------------------------------------------

    private function overview(): void {
        $commStats = $this->accounts->getCommissionStats();
        $expStats  = $this->accounts->getExpenseStats();
        require APP_ROOT . '/app/views/admin/accounts/overview.php';
    }

    // ---- Commission ------------------------------------------------

    private function commission(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/commission');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addCommission($this->commissionPayload($uid));
                AuditLog::log('commission_added', $uid, 'commission', $id, 'Commission record added.');
                $_SESSION['success'] = 'Commission record added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['commission_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateCommission($id, $this->commissionPayload($uid));
                    AuditLog::log('commission_updated', $uid, 'commission', $id, 'Commission record updated.');
                    $_SESSION['success'] = 'Commission record updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['commission_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteCommission($id);
                    AuditLog::log('commission_deleted', $uid, 'commission', $id, 'Commission record deleted.');
                    $_SESSION['success'] = 'Commission record deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/commission');
            exit;
        }

        $filters = [
            'agent_id'        => (int)($_GET['agent_id']        ?? 0),
            'maturity_status' => $_GET['maturity_status'] ?? '',
            'payment_status'  => $_GET['payment_status']  ?? '',
            'sale_month'      => (int)($_GET['sale_month'] ?? 0),
            'sale_year'       => (int)($_GET['sale_year']  ?? 0),
        ];

        $commissions    = $this->accounts->getCommissions($filters);
        $agents         = $this->accounts->getAgents();
        $pendingReasons = $this->accounts->getPendingReasons();
        $stats          = $this->accounts->getCommissionStats();

        require APP_ROOT . '/app/views/admin/accounts/commission.php';
    }

    private function commissionPayload(int $uid): array {
        return [
            'agent_id'          => (int)($_POST['agent_id'] ?? 0),
            'lead_id'           => (int)($_POST['lead_id']  ?? 0),
            'client_name'       => trim($_POST['client_name']  ?? ''),
            'project'           => trim($_POST['project']      ?? ''),
            'plot_number'       => trim($_POST['plot_number']  ?? ''),
            'total_commission'  => (float)($_POST['total_commission'] ?? 0),
            'maturity_status'   => ($_POST['maturity_status'] ?? '') === 'mature' ? 'mature' : 'immature',
            'payment_status'    => ($_POST['payment_status']  ?? '') === 'paid'   ? 'paid'   : 'pending',
            'paid_amount'       => (float)($_POST['paid_amount'] ?? 0),
            'pending_reason_id' => (int)($_POST['pending_reason_id'] ?? 0),
            'pending_notes'     => trim($_POST['pending_notes'] ?? ''),
            'sale_month'        => (int)($_POST['sale_month'] ?? 0),
            'sale_year'         => (int)($_POST['sale_year']  ?? 0),
            'notes'             => trim($_POST['notes'] ?? ''),
            'created_by'        => $uid,
        ];
    }

    // ---- Expenses --------------------------------------------------

    private function expenses(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/expenses');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addExpense($this->expensePayload($uid));
                AuditLog::log('expense_added', $uid, 'expense', $id, 'Expense added.');
                $_SESSION['success'] = 'Expense added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateExpense($id, $this->expensePayload($uid));
                    AuditLog::log('expense_updated', $uid, 'expense', $id, 'Expense updated.');
                    $_SESSION['success'] = 'Expense updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteExpense($id);
                    AuditLog::log('expense_deleted', $uid, 'expense', $id, 'Expense deleted.');
                    $_SESSION['success'] = 'Expense deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/expenses');
            exit;
        }

        $type    = $_GET['type']  ?? '';
        $month   = (int)($_GET['month'] ?? 0);
        $year    = (int)($_GET['year']  ?? 0);

        $expenses = $this->accounts->getExpenses($type, $month, $year);
        $agents   = $this->accounts->getAgents();
        $stats    = $this->accounts->getExpenseStats();

        require APP_ROOT . '/app/views/admin/accounts/expenses.php';
    }

    private function expensePayload(int $uid): array {
        $date  = $_POST['expense_date'] ?? date('Y-m-d');
        $ts    = strtotime($date) ?: time();
        $types = ['marketing', 'salary', 'general'];
        return [
            'type'          => in_array($_POST['type'] ?? '', $types, true) ? $_POST['type'] : 'general',
            'category'      => trim($_POST['category']    ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'amount'        => (float)($_POST['amount'] ?? 0),
            'agent_id'      => (int)($_POST['agent_id']  ?? 0),
            'expense_date'  => date('Y-m-d', $ts),
            'expense_month' => (int)date('n', $ts),
            'expense_year'  => (int)date('Y', $ts),
            'created_by'    => $uid,
        ];
    }

    // ---- Salaries --------------------------------------------------

    private function salaries(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/salaries');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addExpense($this->salaryPayload($uid));
                AuditLog::log('salary_added', $uid, 'expense', $id, 'Salary record added.');
                $_SESSION['success'] = 'Salary record added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateExpense($id, $this->salaryPayload($uid));
                    AuditLog::log('salary_updated', $uid, 'expense', $id, 'Salary record updated.');
                    $_SESSION['success'] = 'Salary record updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteExpense($id);
                    AuditLog::log('salary_deleted', $uid, 'expense', $id, 'Salary record deleted.');
                    $_SESSION['success'] = 'Salary record deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/salaries');
            exit;
        }

        $month   = (int)($_GET['month']    ?? 0);
        $year    = (int)($_GET['year']     ?? 0);
        $agentId = (int)($_GET['agent_id'] ?? 0);

        $salaries = $this->accounts->getExpenses('salary', $month, $year, $agentId);
        $agents   = $this->accounts->getAgents();
        $stats    = $this->accounts->getExpenseStats();

        require APP_ROOT . '/app/views/admin/accounts/salaries.php';
    }

    private function salaryPayload(int $uid): array {
        $month = max(1, min(12, (int)($_POST['salary_month'] ?? date('n'))));
        $year  = (int)($_POST['salary_year'] ?? date('Y'));
        return [
            'type'          => 'salary',
            'category'      => 'Salary',
            'description'   => trim($_POST['description'] ?? ''),
            'amount'        => (float)($_POST['amount'] ?? 0),
            'agent_id'      => (int)($_POST['agent_id'] ?? 0),
            'expense_date'  => sprintf('%04d-%02d-01', $year, $month),
            'expense_month' => $month,
            'expense_year'  => $year,
            'created_by'    => $uid,
        ];
    }
}
