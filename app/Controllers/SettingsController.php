<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Cache;
use App\Core\Logger;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SettingsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        if (!hasRole('admin')) {
            Session::set('error', 'Administrator access required.');
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        $this->view('settings.index');
    }

    public function backup(): void
    {
        $format = strtolower($_GET['format'] ?? 'json');
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $dateStr = date('Y-m-d');
        $filename = 'ExpensePro_Backup_' . $dateStr;

        // 1. Fetch ALL user data
        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $accounts = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $categories = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT t.*, a.name as account_name, c.name as category_name, cur.symbol as currency_symbol
            FROM transactions t 
            LEFT JOIN accounts a ON t.account_id = a.id 
            LEFT JOIN categories c ON t.category_id = c.id 
            LEFT JOIN currencies cur ON t.currency_id = cur.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL 
            ORDER BY t.transaction_date DESC
        ");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM budgets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $budgets = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT b.*, c.name as category_name FROM bills b LEFT JOIN categories c ON b.category_id = c.id WHERE b.user_id = ?");
        $stmt->execute([$userId]);
        $bills = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM bill_payments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $billPayments = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT s.*, e.company_name FROM salaries s JOIN employers e ON s.employer_id = e.id WHERE s.user_id = ?");
        $stmt->execute([$userId]);
        $salaries = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE user_id = ?");
        $stmt->execute([$userId]);
        $vaults = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM daily_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $dailyLogs = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM pending_ledger WHERE user_id = ?");
        $stmt->execute([$userId]);
        $pendingLedger = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM user_fxp_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $fxpStats = $stmt->fetch();

        $stmt = $db->prepare("SELECT * FROM user_mastery_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $masteryStats = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
        $stmt->execute([$userId]);
        $streaks = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM user_achievements WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userAchievements = $stmt->fetchAll();

        // Calculate summary stats
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($transactions as $t) {
            if ($t['type'] === 'income')
                $totalIncome += (float) $t['total_amount'];
            else
                $totalExpense += (float) $t['total_amount'];
        }
        $netIncome = $totalIncome - $totalExpense;

        // ==========================================
        // JSON EXPORT
        // ==========================================
        if ($format === 'json') {
            $data = [
                'export_date' => date('Y-m-d H:i:s'),
                'user_id' => $userId,
                'accounts' => $accounts,
                'categories' => $categories,
                'transactions' => $transactions,
                'budgets' => $budgets,
                'bills' => $bills,
                'bill_payments' => $billPayments,
                'salaries' => $salaries,
                'savings_vaults' => $vaults,
                'daily_logs' => $dailyLogs,
                'pending_ledger' => $pendingLedger,
                'user_fxp_stats' => $fxpStats,
                'user_mastery_stats' => $masteryStats,
                'user_streaks' => $streaks,
                'user_achievements' => $userAchievements
            ];
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ==========================================
        // XLSX EXPORT (Enterprise Styled)
        // ==========================================
        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();

            // Professional Header Style
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
            ];

            $dataStyle = [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]]
            ];

            // 1. Summary Sheet
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Summary');
            $sheet->setCellValue('A1', 'Financial Summary Report');
            $sheet->setCellValue('A2', 'Generated: ' . date('F d, Y H:i:s'));
            $sheet->setCellValue('A4', 'Total Income:');
            $sheet->setCellValue('B4', $totalIncome);
            $sheet->setCellValue('A5', 'Total Expenses:');
            $sheet->setCellValue('B5', $totalExpense);
            $sheet->setCellValue('A6', 'Net Income:');
            $sheet->setCellValue('B6', $netIncome);

            $sheet->getStyle('A1:B2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A4:A6')->getFont()->setBold(true);
            $sheet->getStyle('B4:B6')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(20);

            // Helper to style a sheet
            $styleSheet = function ($ws, $startRow, $endRow, $cols) use ($headerStyle, $dataStyle) {
                $ws->getStyle('A' . $startRow . ':' . $cols . $endRow)->applyFromArray($dataStyle);
                $ws->getStyle('A' . $startRow . ':' . $cols . $startRow)->applyFromArray($headerStyle);
                foreach (range('A', $cols) as $col) {
                    $ws->getColumnDimension($col)->setAutoSize(true);
                }
            };

            // 2. Transactions Sheet
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Transactions');
            $sheet2->fromArray([['Date', 'Type', 'Description', 'Account', 'Category', 'Amount', 'Currency', 'Status']]);
            $r = 2;
            foreach ($transactions as $t) {
                $sheet2->fromArray([
                    [
                        $t['transaction_date'],
                        ucfirst($t['type']),
                        $t['description'] ?: 'N/A',
                        $t['account_name'] ?? 'Unknown',
                        $t['category_name'] ?? 'Unknown',
                        (float) $t['total_amount'],
                        $t['currency_symbol'] ?? '$',
                        ucfirst($t['status'])
                    ]
                ], null, 'A' . $r);

                $amountCell = 'F' . $r;
                $sheet2->getStyle($amountCell)->getFont()->getColor()->setARGB($t['type'] === 'income' ? 'FF10B981' : 'FFEF4444');
                $sheet2->getStyle($amountCell)->getFont()->setBold(true);
                $r++;
            }
            $styleSheet($sheet2, 1, $r - 1, 'H');

            // 3. Accounts Sheet
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Accounts');
            $sheet3->fromArray([['Name', 'Type', 'Institution', 'Current Balance', 'Status']]);
            $r = 2;
            foreach ($accounts as $a) {
                $sheet3->fromArray([
                    [
                        $a['name'],
                        ucfirst(str_replace('_', ' ', $a['type'])),
                        $a['institution'] ?: 'N/A',
                        (float) $a['current_balance'],
                        ucfirst($a['status'])
                    ]
                ], null, 'A' . $r);
                $r++;
            }
            $styleSheet($sheet3, 1, $r - 1, 'E');

            // 4. Bills Sheet
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Bills');
            $sheet4->fromArray([['Name', 'Amount', 'Frequency', 'Next Due Date', 'Category', 'Status']]);
            $r = 2;
            foreach ($bills as $b) {
                $sheet4->fromArray([
                    [
                        $b['name'],
                        (float) $b['total_amount'],
                        ucfirst($b['frequency']),
                        $b['next_due_date'],
                        $b['category_name'] ?? 'N/A',
                        ucfirst($b['status'])
                    ]
                ], null, 'A' . $r);
                $r++;
            }
            $styleSheet($sheet4, 1, $r - 1, 'F');

            // 5. Salaries Sheet
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Salaries');
            $sheet5->fromArray([['Employer', 'Period Start', 'Period End', 'Basic Salary', 'Net Pay', 'Payment Date']]);
            $r = 2;
            foreach ($salaries as $s) {
                $sheet5->fromArray([
                    [
                        $s['company_name'],
                        $s['pay_period_start'],
                        $s['pay_period_end'],
                        (float) $s['basic_salary'],
                        (float) $s['net_pay'],
                        $s['payment_date']
                    ]
                ], null, 'A' . $r);
                $sheet5->getStyle('E' . $r)->getFont()->getColor()->setARGB('FF10B981');
                $r++;
            }
            $styleSheet($sheet5, 1, $r - 1, 'F');

            // 6. Savings Vaults Sheet
            $sheet6 = $spreadsheet->createSheet();
            $sheet6->setTitle('Savings Vaults');
            $sheet6->fromArray([['Goal Name', 'Target Amount', 'Current Amount', 'Progress %', 'Status']]);
            $r = 2;
            foreach ($vaults as $v) {
                $progress = $v['target_amount'] > 0 ? round(($v['current_amount'] / $v['target_amount']) * 100, 1) : 0;
                $sheet6->fromArray([
                    [
                        $v['name'],
                        (float) $v['target_amount'],
                        (float) $v['current_amount'],
                        $progress . '%',
                        ucfirst($v['status'])
                    ]
                ], null, 'A' . $r);
                $r++;
            }
            $styleSheet($sheet6, 1, $r - 1, 'E');

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        // ==========================================
        // CSV EXPORT
        // ==========================================
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo chr(0xEF) . chr(0xBB) . chr(0xBF); // UTF-8 BOM

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Type', 'Description', 'Account', 'Category', 'Amount', 'Currency', 'Status'], ',', '"', '');
            foreach ($transactions as $t) {
                fputcsv($output, [
                    $t['transaction_date'],
                    ucfirst($t['type']),
                    $t['description'] ?: 'N/A',
                    $t['account_name'] ?? 'Unknown',
                    $t['category_name'] ?? 'Unknown',
                    (float) $t['total_amount'],
                    $t['currency_symbol'] ?? '$',
                    ucfirst($t['status'])
                ], ',', '"', '');
            }
            fclose($output);
            exit;
        }

        // ==========================================
        // PDF EXPORT (Modern Enterprise Styled)
        // ==========================================
        if ($format === 'pdf') {
            if (!class_exists('\Mpdf\Mpdf')) {
                die("<h3>PDF Generation Missing Dependency</h3><p>Please run: <code>composer require mpdf/mpdf</code> in your project root.</p>");
            }

            $tempDir = BASE_PATH . '/storage/tmp/mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $tempDir,
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 25,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10
            ]);
            $mpdf->SetAuthor('ExpensePro');
            $mpdf->SetTitle('Complete Financial Report');

            // Professional Header & Footer
            $mpdf->SetHTMLHeader('<div style="text-align: right; font-size: 10px; color: #64748B; border-bottom: 1px solid #E2E8F0; padding-bottom: 5px;">ExpensePro Financial Report • Generated: ' . date('M d, Y') . '</div>');
            $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 10px; color: #64748B; border-top: 1px solid #E2E8F0; padding-top: 5px;">Page {PAGENO} of {nbpg}</div>');

            $html = '
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #334155; }
                    h1 { color: #2563EB; font-size: 22px; margin-bottom: 5px; border-bottom: 2px solid #2563EB; padding-bottom: 8px; }
                    h2 { color: #1E293B; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #CBD5E1; padding-bottom: 4px; }
                    .summary-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin: 15px 0; }
                    .stat { display: inline-block; width: 32%; text-align: center; }
                    .stat-value { font-size: 20px; font-weight: bold; }
                    .stat-label { font-size: 10px; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
                    table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10px; }
                    th { background: #2563EB; color: white; padding: 8px; text-align: left; font-weight: 600; }
                    td { padding: 6px 8px; border-bottom: 1px solid #E2E8F0; }
                    tr:nth-child(even) { background: #F8FAFC; }
                    tr.no-break { page-break-inside: avoid; }
                    .income { color: #10B981; font-weight: bold; }
                    .expense { color: #EF4444; font-weight: bold; }
                    .page-break { page-break-before: always; }
                </style>

                <h1>Complete Financial Report</h1>
                <p style="color: #64748B; margin-top: 0;">Comprehensive overview of your financial activity.</p>

                <div class="summary-box">
                    <div class="stat">
                        <div class="stat-value income">$' . number_format($totalIncome, 2) . '</div>
                        <div class="stat-label">Total Income</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value expense">$' . number_format($totalExpense, 2) . '</div>
                        <div class="stat-label">Total Expenses</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: ' . ($netIncome >= 0 ? '#10B981' : '#EF4444') . ';">$' . number_format($netIncome, 2) . '</div>
                        <div class="stat-label">Net Income</div>
                    </div>
                </div>

                <h2>Accounts Overview</h2>
                <table>
                    <tr><th>Name</th><th>Type</th><th>Institution</th><th style="text-align:right;">Balance</th></tr>
            ';

            foreach ($accounts as $a) {
                $html .= '<tr class="no-break">
                    <td>' . htmlspecialchars($a['name']) . '</td>
                    <td>' . ucfirst(str_replace('_', ' ', $a['type'])) . '</td>
                    <td>' . htmlspecialchars($a['institution'] ?: 'N/A') . '</td>
                    <td style="text-align:right; font-weight:600;">$' . number_format((float) $a['current_balance'], 2) . '</td>
                </tr>';
            }
            $html .= '</table>';

            // Transactions (limit to last 100 for PDF size)
            $html .= '<div class="page-break"></div><h2>Recent Transactions (Last 100)</h2>';
            $html .= '<table><tr><th>Date</th><th>Type</th><th>Description</th><th>Account</th><th style="text-align:right;">Amount</th></tr>';

            $txnCount = 0;
            foreach ($transactions as $t) {
                if ($txnCount >= 100)
                    break;
                $class = $t['type'] === 'income' ? 'income' : 'expense';
                $sign = $t['type'] === 'income' ? '+' : '-';
                $html .= '<tr class="no-break">
                    <td>' . $t['transaction_date'] . '</td>
                    <td>' . ucfirst($t['type']) . '</td>
                    <td>' . htmlspecialchars($t['description'] ?: 'N/A') . '</td>
                    <td>' . htmlspecialchars($t['account_name'] ?? 'Unknown') . '</td>
                    <td style="text-align:right;" class="' . $class . '">' . $sign . '$' . number_format((float) $t['total_amount'], 2) . '</td>
                </tr>';
                $txnCount++;
            }
            $html .= '</table>';

            // Bills
            if (!empty($bills)) {
                $html .= '<div class="page-break"></div><h2>Bills & Recurring Payments</h2>';
                $html .= '<table><tr><th>Name</th><th style="text-align:right;">Amount</th><th>Frequency</th><th>Next Due</th><th>Status</th></tr>';
                foreach ($bills as $b) {
                    $html .= '<tr class="no-break">
                        <td>' . htmlspecialchars($b['name']) . '</td>
                        <td style="text-align:right;">$' . number_format((float) $b['total_amount'], 2) . '</td>
                        <td>' . ucfirst($b['frequency']) . '</td>
                        <td>' . $b['next_due_date'] . '</td>
                        <td>' . ucfirst($b['status']) . '</td>
                    </tr>';
                }
                $html .= '</table>';
            }

            // Salaries
            if (!empty($salaries)) {
                $html .= '<div class="page-break"></div><h2>Salary Records</h2>';
                $html .= '<table><tr><th>Employer</th><th>Period</th><th style="text-align:right;">Basic</th><th style="text-align:right;">Net Pay</th><th>Date</th></tr>';
                foreach ($salaries as $s) {
                    $html .= '<tr class="no-break">
                        <td>' . htmlspecialchars($s['company_name']) . '</td>
                        <td>' . $s['pay_period_start'] . ' to ' . $s['pay_period_end'] . '</td>
                        <td style="text-align:right;">$' . number_format((float) $s['basic_salary'], 2) . '</td>
                        <td style="text-align:right;" class="income">$' . number_format((float) $s['net_pay'], 2) . '</td>
                        <td>' . $s['payment_date'] . '</td>
                    </tr>';
                }
                $html .= '</table>';
            }

            // Savings Vaults
            if (!empty($vaults)) {
                $html .= '<div class="page-break"></div><h2>Savings Vaults</h2>';
                $html .= '<table><tr><th>Goal</th><th style="text-align:right;">Target</th><th style="text-align:right;">Saved</th><th style="text-align:right;">Progress</th><th>Status</th></tr>';
                foreach ($vaults as $v) {
                    $progress = $v['target_amount'] > 0 ? round(($v['current_amount'] / $v['target_amount']) * 100, 1) : 0;
                    $html .= '<tr class="no-break">
                        <td>' . htmlspecialchars($v['name']) . '</td>
                        <td style="text-align:right;">$' . number_format((float) $v['target_amount'], 2) . '</td>
                        <td style="text-align:right;">$' . number_format((float) $v['current_amount'], 2) . '</td>
                        <td style="text-align:right;">' . $progress . '%</td>
                        <td>' . ucfirst($v['status']) . '</td>
                    </tr>';
                }
                $html .= '</table>';
            }

            $mpdf->WriteHTML($html);
            $mpdf->Output($filename . '.pdf', 'D');
            exit;
        }

        Session::set('error', 'Invalid backup format requested.');
        $this->redirect('/settings');
    }

    public function restore(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';
        $file = $_FILES['backup_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('error', 'No file uploaded or upload failed.');
            $this->redirect('/settings');
        }

        if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            Session::set('error', 'Invalid file type. Please upload a .json backup file.');
            $this->redirect('/settings');
        }

        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::set('error', 'Incorrect password. Restore cancelled for security.');
            $this->redirect('/settings');
        }

        $jsonContent = file_get_contents($file['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['user_id']) || $data['user_id'] !== $userId) {
            Session::set('error', 'Invalid or corrupted backup file. User ID mismatch.');
            $this->redirect('/settings');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            // 1. Delete existing data (Child to Parent to respect FKs)
            $deleteOrder = [
                'user_achievements',
                'user_streaks',
                'user_mastery_stats',
                'user_fxp_stats',
                'transaction_tags',
                'transaction_splits',
                'transactions',
                'vault_transactions',
                'savings_vaults',
                'bill_payments',
                'bills',
                'salaries',
                'employers',
                'budgets',
                'categories',
                'accounts',
                'tags',
                'daily_logs',
                'pending_ledger',
                'radar_alerts',
                'timeline_events'
            ];
            foreach ($deleteOrder as $table) {
                $db->prepare("DELETE FROM `$table` WHERE user_id = ?")->execute([$userId]);
            }

            $idMap = [];

            // 2. Insert new data (Parent to Child) with ID Mapping
            if (!empty($data['accounts'])) {
                $stmt = $db->prepare("INSERT INTO accounts (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, notes, status, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['accounts'] as $row) {
                    $stmt->execute([$userId, $row['currency_id'], $row['name'], $row['type'], $row['institution'], $row['account_number'], $row['opening_balance'], $row['current_balance'], $row['notes'], $row['status'], $row['created_at'], $row['updated_at'], $row['deleted_at']]);
                    $idMap['accounts'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['categories'])) {
                $stmt = $db->prepare("INSERT INTO categories (user_id, parent_id, name, type, color, icon, created_at, deleted_at, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['categories'] as $row) {
                    $stmt->execute([$userId, $row['parent_id'], $row['name'], $row['type'], $row['color'], $row['icon'], $row['created_at'], $row['deleted_at'], $row['is_archived'] ?? 0]);
                    $idMap['categories'][$row['id']] = $db->lastInsertId();
                }
                foreach ($data['categories'] as $row) {
                    if (!empty($row['parent_id']) && isset($idMap['categories'][$row['parent_id']])) {
                        $db->prepare("UPDATE categories SET parent_id = ? WHERE id = ?")->execute([$idMap['categories'][$row['parent_id']], $idMap['categories'][$row['id']]]);
                    }
                }
            }

            if (!empty($data['transactions'])) {
                $stmt = $db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, converted_amount, description, notes, is_favorite, transaction_date, status, is_recurring, recurring_rule, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['transactions'] as $row) {
                    $newAccountId = $idMap['accounts'][$row['account_id']] ?? null;
                    $newCategoryId = $idMap['categories'][$row['category_id']] ?? null;
                    $stmt->execute([$userId, $newAccountId, $newCategoryId, $row['type'], $row['total_amount'], $row['currency_id'], $row['converted_amount'], $row['description'], $row['notes'], $row['is_favorite'], $row['transaction_date'], $row['status'], $row['is_recurring'], $row['recurring_rule'], $row['created_at'], $row['updated_at'], $row['deleted_at']]);
                    $idMap['transactions'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['transaction_splits'])) {
                $stmt = $db->prepare("INSERT INTO transaction_splits (transaction_id, category_id, amount, notes) VALUES (?, ?, ?, ?)");
                foreach ($data['transaction_splits'] as $row) {
                    $newTxnId = $idMap['transactions'][$row['transaction_id']] ?? null;
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;
                    if ($newTxnId && $newCatId) {
                        $stmt->execute([$newTxnId, $newCatId, $row['amount'], $row['notes']]);
                    }
                }
            }

            $simpleInserts = [
                'tags',
                'budgets',
                'employers',
                'salaries',
                'bills',
                'bill_payments',
                'savings_vaults',
                'vault_transactions',
                'daily_logs',
                'pending_ledger',
                'radar_alerts',
                'user_fxp_stats',
                'user_mastery_stats',
                'user_streaks',
                'user_achievements'
            ];

            foreach ($simpleInserts as $table) {
                if (!empty($data[$table])) {
                    $columns = array_keys($data[$table][0]);
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $colsStr = implode(',', $columns);
                    $stmt = $db->prepare("INSERT INTO `$table` ($colsStr) VALUES ($placeholders)");
                    foreach ($data[$table] as $row) {
                        $row['user_id'] = $userId;
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $row[$col];
                        }
                        $stmt->execute($values);
                        if (isset($row['id'])) {
                            $idMap[$table][$row['id']] = $db->lastInsertId();
                        }
                    }
                }
            }

            $db->commit();
            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");
            Logger::info("Data restored successfully", ['user_id' => $userId]);
            Session::set('success', 'Data restored successfully! All old data has been replaced.');
            $this->redirect('/settings');

        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Restore failed", ['user_id' => $userId, 'error' => $e->getMessage()]);
            Session::set('error', 'Restore failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function deleteAll(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';

        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::set('error', 'Incorrect password. Delete operation cancelled.');
            $this->redirect('/settings');
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tablesToTruncate = [
                'user_achievements',
                'user_streaks',
                'user_mastery_stats',
                'user_fxp_stats',
                'transactions',
                'transaction_splits',
                'transaction_tags',
                'accounts',
                'categories',
                'tags',
                'budgets',
                'bills',
                'bill_payments',
                'salaries',
                'employers',
                'savings_vaults',
                'vault_transactions',
                'daily_logs',
                'pending_ledger',
                'radar_alerts',
                'timeline_events',
                'forecast_scenarios',
                'user_preferences',
                'audit_logs'
            ];

            foreach ($tablesToTruncate as $table) {
                $db->exec("TRUNCATE TABLE `{$table}`");
            }

            $db->exec("SET FOREIGN_KEY_CHECKS = 1");

            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");

            Logger::info("Admin deleted all financial data", [
                'admin_id' => $userId,
                'admin_email' => $user['email'],
                'tables_affected' => count($tablesToTruncate)
            ]);

            Session::set('success', 'All financial data has been deleted. User accounts remain intact.');
            $this->redirect('/settings');

        } catch (\Exception $e) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            Logger::error("Delete all data failed", ['admin_id' => $userId, 'error' => $e->getMessage()]);
            Session::set('error', 'Failed to delete data: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }
}