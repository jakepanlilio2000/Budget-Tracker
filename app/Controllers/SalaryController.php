<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\CurrencyService;
use App\Models\Salary;
use App\Models\Employer;
use App\Services\AchievementEngine;
use App\Services\TimelineService;
use App\Services\FxpEngine;
use App\Services\LifetimeStatsService;

class SalaryController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $salaries = Salary::getRecent(Auth::id(), 50);
        $analytics = Salary::getAnalytics(Auth::id());
        $this->view('salaries.index', ['salaries' => $salaries, 'analytics' => $analytics]);
    }

    public function create(): void
    {
        $employers = Employer::getAllByUser(Auth::id());
        $this->view('salaries.create', ['employers' => $employers]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $employerId = (int) ($_POST['employer_id'] ?? 0);
        if ($employerId === 0 && !empty($_POST['new_employer_name'])) {
            $employerId = Employer::create($userId, [
                'company_name' => trim($_POST['new_employer_name']),
                'contact_email' => trim($_POST['new_employer_email'] ?? ''),
                'contact_phone' => trim($_POST['new_employer_phone'] ?? '')
            ]);
        }

        if ($employerId <= 0) {
            Session::set('error', 'Please select or create an employer.');
            $this->redirect('/salaries/create');
        }

        $allowances = [];
        if (!empty($_POST['allowance_name'])) {
            foreach ($_POST['allowance_name'] as $i => $name) {
                if (!empty($name) && (float) ($_POST['allowance_amount'][$i] ?? 0) > 0) {
                    $allowances[] = ['name' => $name, 'amount' => (float) $_POST['allowance_amount'][$i]];
                }
            }
        }

        $deductions = [];
        if (!empty($_POST['deduction_name'])) {
            foreach ($_POST['deduction_name'] as $i => $name) {
                if (!empty($name) && (float) ($_POST['deduction_amount'][$i] ?? 0) > 0) {
                    $deductions[] = ['name' => $name, 'amount' => (float) $_POST['deduction_amount'][$i]];
                }
            }
        }

        $basic = (float) ($_POST['basic_salary'] ?? 0);
        $bonus = (float) ($_POST['bonus'] ?? 0);
        $overtime = (float) ($_POST['overtime_pay'] ?? 0);
        $thirteenth = (float) ($_POST['thirteenth_month'] ?? 0);
        $totalAllowances = array_sum(array_column($allowances, 'amount'));
        $totalDeductions = array_sum(array_column($deductions, 'amount'));

        $netPay = ($basic + $bonus + $overtime + $thirteenth + $totalAllowances) - $totalDeductions;

        $data = [
            'employer_id' => $employerId,
            'pay_period_start' => $_POST['pay_period_start'],
            'pay_period_end' => $_POST['pay_period_end'],
            'basic_salary' => $basic,
            'bonus' => $bonus,
            'overtime_pay' => $overtime,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'thirteenth_month' => $thirteenth,
            'net_pay' => $netPay,
            'payment_date' => $_POST['payment_date'],
            'status' => $_POST['status'] ?? 'paid',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        $salaryId = Salary::create($userId, $data);


        $employer = Employer::findById($employerId, $userId);
        $data['employer_name'] = $employer['company_name'] ?? 'Unknown';
        $data['id'] = $salaryId;
        $data['currency_id'] = CurrencyService::getUserBaseCurrency($userId)['id'];

        TimelineService::logSalary($userId, $data);
        Session::set('success', 'Payslip saved successfully.');
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        FxpEngine::award($userId, 'receive_salary', 1);
        LifetimeStatsService::clearCache($userId);
        $this->redirect('/salaries');
    }
    public function show(int $id): void
    {
        $salary = Salary::findById($id, Auth::id());
        if (!$salary) {
            Session::set('error', 'Payslip not found.');
            $this->redirect('/salaries');
        }

        $salary['allowances'] = json_decode($salary['allowances'], true) ?: [];
        $salary['deductions'] = json_decode($salary['deductions'], true) ?: [];
        $this->view('salaries.view', ['salary' => $salary]);
    }

    public function exportCsv(): void
    {
        $salaries = Salary::getRecent(Auth::id(), 1000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="salary_history.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
        fputcsv($output, ['Period Start', 'Period End', 'Employer', 'Basic', 'Bonus', 'Overtime', 'Allowances', 'Deductions', 'Net Pay', 'Date'], ',', '"', '');

        foreach ($salaries as $s) {
            $allowances = array_sum(array_column(json_decode($s['allowances'], true) ?: [], 'amount'));
            $deductions = array_sum(array_column(json_decode($s['deductions'], true) ?: [], 'amount'));

            fputcsv($output, [
                $s['pay_period_start'],
                $s['pay_period_end'],
                $s['company_name'],
                $s['basic_salary'],
                $s['bonus'],
                $s['overtime_pay'],
                $allowances,
                $deductions,
                $s['net_pay'],
                $s['payment_date']
            ], ',', '"', '');
        }
        fclose($output);
        exit;
    }

    public function edit(int $id): void
    {
        $salary = Salary::findById($id, Auth::id());
        if (!$salary) {
            Session::set('error', 'Payslip not found.');
            $this->redirect('/salaries');
        }
        $employers = Employer::getAllByUser(Auth::id());
        $this->view('salaries.edit', ['salary' => $salary, 'employers' => $employers]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $salary = Salary::findById($id, $userId);
        if (!$salary) {
            Session::set('error', 'Payslip not found.');
            $this->redirect('/salaries');
        }

        $allowances = [];
        if (!empty($_POST['allowance_name'])) {
            foreach ($_POST['allowance_name'] as $i => $name) {
                if (!empty($name) && (float) ($_POST['allowance_amount'][$i] ?? 0) > 0) {
                    $allowances[] = ['name' => $name, 'amount' => (float) $_POST['allowance_amount'][$i]];
                }
            }
        }

        $deductions = [];
        if (!empty($_POST['deduction_name'])) {
            foreach ($_POST['deduction_name'] as $i => $name) {
                if (!empty($name) && (float) ($_POST['deduction_amount'][$i] ?? 0) > 0) {
                    $deductions[] = ['name' => $name, 'amount' => (float) $_POST['deduction_amount'][$i]];
                }
            }
        }

        $basic = (float) ($_POST['basic_salary'] ?? 0);
        $bonus = (float) ($_POST['bonus'] ?? 0);
        $overtime = (float) ($_POST['overtime_pay'] ?? 0);
        $thirteenth = (float) ($_POST['thirteenth_month'] ?? 0);
        $totalAllowances = array_sum(array_column($allowances, 'amount'));
        $totalDeductions = array_sum(array_column($deductions, 'amount'));

        $netPay = ($basic + $bonus + $overtime + $thirteenth + $totalAllowances) - $totalDeductions;

        $data = [
            'employer_id' => (int) ($_POST['employer_id'] ?? 0),
            'pay_period_start' => $_POST['pay_period_start'],
            'pay_period_end' => $_POST['pay_period_end'],
            'basic_salary' => $basic,
            'bonus' => $bonus,
            'overtime_pay' => $overtime,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'thirteenth_month' => $thirteenth,
            'net_pay' => $netPay,
            'payment_date' => $_POST['payment_date'],
            'status' => $_POST['status'] ?? 'paid',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        Salary::update($id, $userId, $data);
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        FxpEngine::award($userId, 'receive_salary', 1);
        LifetimeStatsService::clearCache($userId);
        Session::set('success', 'Payslip updated successfully.');
        $this->redirect('/salaries');
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $salary = Salary::findById($id, Auth::id());
        if (!$salary) {
            Session::set('error', 'Payslip not found.');
            $this->redirect('/salaries');
        }

        Salary::delete($id, Auth::id());
        AchievementEngine::syncUser(Auth::id());

        Session::set('success', 'Payslip deleted successfully.');
        $this->redirect('/salaries');
    }
}