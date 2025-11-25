<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\OpeningBalance;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SakepReportService
{
    /**
     * Generate Trial Balance using SAKEP
     */
    public function generateTrialBalance(int $companyId, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        // Get company's accounting standard
        $company = Company::with('standard')->find($companyId);
        if (!$company || !$company->accounting_standard_id) {
            throw new \Exception('Company accounting standard not found');
        }

        // Get all journal details with SAKEP relationships up to the date
        $journalDetails = JournalDetail::with(['journal', 'kelompok', 'rekening', 'nomorBantu'])
            ->whereHas('journal', function ($query) use ($companyId, $asOfDate) {
                $query->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('transaction_date', '<=', $asOfDate);
            })
            ->get();

        // Get opening balances for the exact date (latest available before as_of_date)
        $openingBalances = OpeningBalance::where('company_id', $companyId)
            ->where('as_of_date', '<=', $asOfDate)
            ->with(['kelompok', 'rekening', 'nomorBantu'])
            ->get()
            ->groupBy($this->getSakepKey(...))
            ->map(fn($group) => $group->sortByDesc('as_of_date')->first());

        // Group by SAKEP account
        $accounts = [];

        // Process journal details
        foreach ($journalDetails as $detail) {
            $key = $this->getSakepKey($detail);

            if (!isset($accounts[$key])) {
                $accounts[$key] = [
                    'sakep_code' => $detail->sakep_code,
                    'account_name' => $detail->account_name,
                    'kelompok' => $detail->kelompok,
                    'rekening' => $detail->rekening,
                    'nomor_bantu' => $detail->nomorBantu,
                    'opening_debit' => 0,
                    'opening_credit' => 0,
                    'period_debit' => 0,
                    'period_credit' => 0,
                ];
            }

            $accounts[$key]['period_debit'] += (float) $detail->debit;
            $accounts[$key]['period_credit'] += (float) $detail->credit;
        }

        // Process opening balances
        foreach ($openingBalances as $opening) {
            $key = $this->getSakepKey($opening);

            if (!isset($accounts[$key])) {
                $accounts[$key] = [
                    'sakep_code' => $opening->sakep_code,
                    'account_name' => $opening->account_name,
                    'kelompok' => $opening->kelompok,
                    'rekening' => $opening->rekening,
                    'nomor_bantu' => $opening->nomorBantu,
                    'opening_debit' => 0,
                    'opening_credit' => 0,
                    'period_debit' => 0,
                    'period_credit' => 0,
                ];
            }

            $accounts[$key]['opening_debit'] += (float) $opening->debit_balance;
            $accounts[$key]['opening_credit'] += (float) $opening->credit_balance;
        }

        // Calculate balances
        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            // Skip accounts with no activity and no opening balances
            if (
                $account['opening_debit'] == 0 && $account['opening_credit'] == 0 &&
                $account['period_debit'] == 0 && $account['period_credit'] == 0
            ) {
                continue;
            }

            $totalDebits = $account['opening_debit'] + $account['period_debit'];
            $totalCredits = $account['opening_credit'] + $account['period_credit'];
            $balance = $totalDebits - $totalCredits;

            $accountData = [
                'sakep_code' => $account['sakep_code'],
                'account_name' => $account['account_name'],
                'opening_debit' => $account['opening_debit'],
                'opening_credit' => $account['opening_credit'],
                'period_debit' => $account['period_debit'],
                'period_credit' => $account['period_credit'],
                'total_debit' => $totalDebits,
                'total_credit' => $totalCredits,
                'balance' => abs($balance),
                'balance_type' => $balance >= 0 ? 'debit' : 'credit',
                'kelompok' => $account['kelompok']?->nama_kel,
                'kelompok_code' => $account['kelompok']?->no_kel,
            ];

            if ($balance >= 0) {
                $totalDebit += abs($balance);
            } else {
                $totalCredit += abs($balance);
            }

            $trialBalance[] = $accountData;
        }

        // Sort by SAKEP code
        usort($trialBalance, fn($a, $b) => strcmp($a['sakep_code'], $b['sakep_code']));

        return [
            'company_id' => $companyId,
            'as_of_date' => $asOfDate->toDateString(),
            'accounts' => $trialBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * Generate Balance Sheet (Neraca) by SAKEP groups
     */
    public function generateBalanceSheet(int $companyId, ?string $asOfDate = null): array
    {
        $trialBalance = $this->generateTrialBalance($companyId, $asOfDate);

        $assets = [];
        $liabilities = [];
        $equity = [];

        foreach ($trialBalance['accounts'] as $account) {
            $kelompokCode = $account['kelompok_code'];

            // SAKEP Classification:
            // 10-45: Assets (Aktiva)
            // 50-62: Liabilities (Kewajiban)
            // 70: Equity (Modal)
            if ($kelompokCode >= 10 && $kelompokCode <= 45) {
                $assets[] = $account;
            } elseif ($kelompokCode >= 50 && $kelompokCode <= 62) {
                $liabilities[] = $account;
            } elseif ($kelompokCode == 70) {
                $equity[] = $account;
            }
        }

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));

        return [
            'company_id' => $companyId,
            'as_of_date' => $asOfDate ?? now()->toDateString(),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Generate Income Statement (Laba Rugi)
     */
    public function generateIncomeStatement(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfYear();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Get journal details for income statement accounts
        $journalDetails = JournalDetail::with(['journal', 'kelompok', 'rekening', 'nomorBantu'])
            ->whereHas('journal', function ($query) use ($companyId, $startDate, $endDate) {
                $query->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get();

        $revenues = [];
        $expenses = [];

        foreach ($journalDetails as $detail) {
            $kelompokCode = $detail->kelompok?->no_kel;
            $key = $this->getSakepKey($detail);

            // SAKEP Classification:
            // 80-88: Revenue (Pendapatan)
            // 91-99: Expenses (Biaya/Beban)
            if ($kelompokCode >= 80 && $kelompokCode <= 88) {
                if (!isset($revenues[$key])) {
                    $revenues[$key] = [
                        'sakep_code' => $detail->sakep_code,
                        'account_name' => $detail->account_name,
                        'amount' => 0,
                        'kelompok_code' => $kelompokCode,
                        'kelompok_name' => $detail->kelompok->nama_kel,
                    ];
                }
                // Revenue accounts typically have credit balances
                $revenues[$key]['amount'] += (float) $detail->credit - (float) $detail->debit;
            } elseif ($kelompokCode >= 91 && $kelompokCode <= 99) {
                if (!isset($expenses[$key])) {
                    $expenses[$key] = [
                        'sakep_code' => $detail->sakep_code,
                        'account_name' => $detail->account_name,
                        'amount' => 0,
                        'kelompok_code' => $kelompokCode,
                        'kelompok_name' => $detail->kelompok->nama_kel,
                    ];
                }
                // Expense accounts typically have debit balances
                $expenses[$key]['amount'] += (float) $detail->debit - (float) $detail->credit;
            }
        }

        $revenues = array_values($revenues);
        $expenses = array_values($expenses);

        // Sort by SAKEP code
        usort($revenues, fn($a, $b) => strcmp($a['sakep_code'], $b['sakep_code']));
        usort($expenses, fn($a, $b) => strcmp($a['sakep_code'], $b['sakep_code']));

        $totalRevenues = array_sum(array_column($revenues, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netIncome = $totalRevenues - $totalExpenses;

        return [
            'company_id' => $companyId,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenues' => $totalRevenues,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $totalRevenues, // Simplified - could separate operating vs non-operating
            'net_income' => $netIncome,
        ];
    }

    /**
     * Generate Cash Flow Statement (Laporan Arus Kas)
     */
    public function generateCashFlowStatement(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfYear();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Get journal details for cash and cash equivalents
        $journalDetails = JournalDetail::with(['journal', 'kelompok', 'rekening', 'nomorBantu'])
            ->whereHas('journal', function ($query) use ($companyId, $startDate, $endDate) {
                $query->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get();

        // Initialize cash flow categories
        $operatingActivities = [];
        $investingActivities = [];
        $financingActivities = [];

        foreach ($journalDetails as $detail) {
            $kelompokCode = $detail->kelompok?->no_kel;
            $rekeningCode = $detail->rekening?->no_rek;

            $activity = [
                'date' => $detail->journal->transaction_date,
                'description' => $detail->description ?: $detail->journal->description,
                'reference' => $detail->journal->reference,
                'amount' => (float) $detail->debit - (float) $detail->credit, // Net cash flow
                'sakep_code' => $detail->sakep_code,
                'account_name' => $detail->account_name,
            ];

            // Classify activities based on SAKEP structure
            if ($this->isOperatingActivity($kelompokCode, $rekeningCode)) {
                $operatingActivities[] = $activity;
            } elseif ($this->isInvestingActivity($kelompokCode, $rekeningCode)) {
                $investingActivities[] = $activity;
            } elseif ($this->isFinancingActivity($kelompokCode, $rekeningCode)) {
                $financingActivities[] = $activity;
            }
        }

        // Calculate totals
        $netOperatingCashFlow = array_sum(array_column($operatingActivities, 'amount'));
        $netInvestingCashFlow = array_sum(array_column($investingActivities, 'amount'));
        $netFinancingCashFlow = array_sum(array_column($financingActivities, 'amount'));
        $netCashFlow = $netOperatingCashFlow + $netInvestingCashFlow + $netFinancingCashFlow;

        // Get beginning and ending cash balances
        $beginningCash = $this->getCashBalance($companyId, $startDate->copy()->subDay());
        $endingCash = $this->getCashBalance($companyId, $endDate);

        return [
            'company_id' => $companyId,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_operating_cash_flow' => $netOperatingCashFlow,
            'net_investing_cash_flow' => $netInvestingCashFlow,
            'net_financing_cash_flow' => $netFinancingCashFlow,
            'net_change_in_cash' => $netCashFlow,
            'beginning_cash_balance' => $beginningCash,
            'ending_cash_balance' => $endingCash,
            'calculated_ending_cash' => $beginningCash + $netCashFlow,
        ];
    }

    /**
     * Determine if transaction is an operating activity
     */
    private function isOperatingActivity(?int $kelompokCode, ?int $rekeningCode): bool
    {
        if (!$kelompokCode) return false;

        // Operating activities typically involve:
        // - Revenue accounts (80-88)
        // - Operating expense accounts (91-96)
        // - Working capital changes (current assets/liabilities)
        return ($kelompokCode >= 80 && $kelompokCode <= 88) ||
            ($kelompokCode >= 91 && $kelompokCode <= 96) ||
            ($kelompokCode == 10 && !in_array($rekeningCode, [1101, 1102])) || // Current assets except cash
            ($kelompokCode == 50); // Current liabilities
    }

    /**
     * Determine if transaction is an investing activity
     */
    private function isInvestingActivity(?int $kelompokCode, ?int $rekeningCode): bool
    {
        if (!$kelompokCode) return false;

        // Investing activities typically involve:
        // - Fixed assets (30)
        // - Long-term investments (20)
        // - Asset disposals/acquisitions
        return ($kelompokCode == 20) || // Long-term investments
            ($kelompokCode == 30) || // Fixed assets
            ($kelompokCode >= 40 && $kelompokCode <= 45); // Other long-term assets
    }

    /**
     * Determine if transaction is a financing activity
     */
    private function isFinancingActivity(?int $kelompokCode, ?int $rekeningCode): bool
    {
        if (!$kelompokCode) return false;

        // Financing activities typically involve:
        // - Long-term debt (60-62)
        // - Equity transactions (70)
        return ($kelompokCode >= 60 && $kelompokCode <= 62) || // Long-term debt & other long-term liabilities
            ($kelompokCode == 70); // Equity
    }

    /**
     * Get cash balance at a specific date
     */
    private function getCashBalance(int $companyId, Carbon $date): float
    {
        // Get cash balances from accounts 1101 (Kas) and 1102 (Bank)
        $cashBalance = JournalDetail::with(['journal'])
            ->whereHas('journal', function ($query) use ($companyId, $date) {
                $query->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('transaction_date', '<=', $date);
            })
            ->whereHas('rekening', function ($query) {
                $query->whereIn('no_rek', [1101, 1102]); // Cash and Bank accounts
            })
            ->get()
            ->sum(fn($detail) => (float) $detail->debit - (float) $detail->credit);

        // Add opening balances for cash accounts
        $openingBalance = OpeningBalance::where('company_id', $companyId)
            ->where('as_of_date', '<=', $date)
            ->whereHas('rekening', function ($query) {
                $query->whereIn('no_rek', [1101, 1102]);
            })
            ->get()
            ->sum(fn($balance) => (float) $balance->debit_balance - (float) $balance->credit_balance);

        return $cashBalance + $openingBalance;
    }

    /**
     * Get General Ledger for specific account
     */
    public function getGeneralLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->getAccountActivity($accountId, 'nomor_bantu', $startDate, $endDate);
    }

    /**
     * Get unique SAKEP key for grouping
     */
    private function getSakepKey($sakepModel): string
    {
        if ($sakepModel->nomor_bantu_id) {
            return 'nb_' . $sakepModel->nomor_bantu_id;
        } elseif ($sakepModel->rekening_id) {
            return 'rek_' . $sakepModel->rekening_id;
        } else {
            return 'kel_' . $sakepModel->kelompok_id;
        }
    }

    /**
     * Get account activity report
     */
    public function getAccountActivity(int $accountId, string $type, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $query = JournalDetail::with(['journal'])
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            });

        // Filter by SAKEP type and ID
        switch ($type) {
            case 'nomor_bantu':
                $query->where('nomor_bantu_id', $accountId);
                $account = NomorBantu::with(['rekening.kelompok'])->find($accountId);
                break;
            case 'rekening':
                $query->where('rekening_id', $accountId);
                $account = Rekening::with('kelompok')->find($accountId);
                break;
            case 'kelompok':
                $query->where('kelompok_id', $accountId);
                $account = Kelompok::find($accountId);
                break;
            default:
                throw new \InvalidArgumentException('Invalid account type: ' . $type);
        }

        $transactions = $query->orderBy('line_number')->get();

        // Get opening balance
        $openingBalance = $this->getAccountOpeningBalance($accountId, $type, $startDate);
        $runningBalance = $openingBalance;
        $activityData = [];

        // Add opening balance entry if non-zero
        if (abs($openingBalance) > 0.01) {
            $activityData[] = [
                'date' => $startDate->format('Y-m-d'),
                'description' => 'Saldo Awal',
                'reference' => 'OPENING',
                'debit' => $openingBalance > 0 ? $openingBalance : 0,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                'balance' => $runningBalance,
            ];
        }

        foreach ($transactions as $transaction) {
            $runningBalance += (float) $transaction->debit - (float) $transaction->credit;

            $activityData[] = [
                'date' => $transaction->journal->transaction_date,
                'description' => $transaction->description ?: $transaction->journal->description,
                'reference' => $transaction->journal->reference,
                'debit' => (float) $transaction->debit,
                'credit' => (float) $transaction->credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => $account,
            'account_type' => $type,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'opening_balance' => $openingBalance,
            'transactions' => $activityData,
            'total_debit' => $transactions->sum('debit') + max(0, $openingBalance),
            'total_credit' => $transactions->sum('credit') + max(0, -$openingBalance),
            'ending_balance' => $runningBalance,
        ];
    }

    /**
     * Get opening balance for specific account
     */
    private function getAccountOpeningBalance(int $accountId, string $type, Carbon $startDate): float
    {
        $query = OpeningBalance::where('as_of_date', '<', $startDate);

        switch ($type) {
            case 'nomor_bantu':
                $query->where('nomor_bantu_id', $accountId);
                break;
            case 'rekening':
                $query->where('rekening_id', $accountId);
                break;
            case 'kelompok':
                $query->where('kelompok_id', $accountId);
                break;
        }

        $openingBalances = $query->get();

        return $openingBalances->sum(
            fn($balance) =>
            (float) $balance->debit_balance - (float) $balance->credit_balance
        );
    }
}
