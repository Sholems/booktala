<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayrollAddDeductionRequest;
use App\Http\Requests\Admin\PayrollGenerateRequest;
use App\Http\Requests\Admin\PayrollUpdateSalaryRequest;
use App\Http\Requests\Admin\PayrollUpdateSettingsRequest;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffDeduction;
use App\Models\User;
use App\Services\PayrollSettings;
use App\Services\PayRunService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll: salary structures and deductions per staff member, statutory rates,
 * and monthly pay runs with payslips, a bank schedule and cost per site.
 */
class PayrollController extends Controller
{
    public function __construct(private PayRunService $runs) {}

    public function index()
    {
        $runs = PayRun::orderByDesc('period_start')->paginate(24);
        $withoutSalary = Staff::where('is_active', true)->whereDoesntHave('salaryStructure')->with('user')->get();

        return view('admin.payroll.index', compact('runs', 'withoutSalary'));
    }

    public function generate(PayrollGenerateRequest $request)
    {
        $data = $request->validated();
        $month = CarbonImmutable::parse($data['month'].'-01');
        abort_if($month->isAfter(now()->startOfMonth()), 422, 'Pay runs cannot be prepared for future months.');

        $run = $this->runs->generate($month, $request->user());

        return redirect()->route('admin.payroll.show', $run)->with('success', 'Pay run calculated for '.$run->periodLabel().'. Review it before approving.');
    }

    public function show(PayRun $payRun)
    {
        $payRun->load(['payslips.staff.user', 'approvedBy']);

        // Employer cost (gross + employer pension) split across sites by hours worked.
        $siteCosts = [];
        foreach ($payRun->payslips as $slip) {
            $cost = (float) $slip->gross + (float) $slip->pension_employer;
            $split = $slip->details['site_split'] ?? [];
            if (! $split) {
                $siteCosts['unallocated'] = ($siteCosts['unallocated'] ?? 0) + $cost;
            }
            foreach ($split as $siteId => $share) {
                $siteCosts[$siteId] = ($siteCosts[$siteId] ?? 0) + $cost * $share;
            }
        }
        $sites = Site::whereIn('id', array_filter(array_keys($siteCosts), 'is_int'))->pluck('name', 'id');

        return view('admin.payroll.show', ['run' => $payRun, 'siteCosts' => $siteCosts, 'sites' => $sites]);
    }

    public function approve(Request $request, PayRun $payRun)
    {
        $this->runs->approve($payRun, $request->user());

        return back()->with('success', 'Pay run approved. Staff can now see their payslips.');
    }

    public function markPaid(PayRun $payRun)
    {
        $this->runs->markPaid($payRun);

        return back()->with('success', 'Pay run marked as paid; loan balances updated.');
    }

    public function destroy(PayRun $payRun)
    {
        if (! $payRun->isDraft()) {
            return back()->with('error', 'Only a draft pay run can be deleted.');
        }
        $payRun->delete();

        return redirect()->route('admin.payroll.index')->with('success', 'Draft pay run deleted');
    }

    /**
     * Bank payment schedule for the run (CSV), for upload to the bank or manual transfer.
     */
    public function bankSchedule(PayRun $payRun): StreamedResponse
    {
        abort_if($payRun->isDraft(), 422, 'Approve the pay run before exporting the bank schedule.');
        $payRun->load('payslips.staff.user');

        return response()->streamDownload(function () use ($payRun) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee ID', 'Name', 'Bank', 'Account name', 'Account number', 'Net pay', 'Narration']);
            foreach ($payRun->payslips as $slip) {
                fputcsv($out, [
                    $slip->staff->employee_id,
                    $slip->staff->user->name ?? '',
                    $slip->staff->bank_name ?? '',
                    $slip->staff->bank_account_name ?? '',
                    // Leading apostrophe keeps spreadsheet apps from dropping leading zeros.
                    $slip->staff->bank_account_number ? "'".$slip->staff->bank_account_number : '',
                    number_format((float) $slip->net, 2, '.', ''),
                    'Salary '.$payRun->periodLabel(),
                ]);
            }
            fclose($out);
        }, 'bank-schedule-'.$payRun->period_start->format('Y-m').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function payslipPdf(Payslip $payslip)
    {
        return self::downloadPayslip($payslip);
    }

    /** Shared with the staff portal. */
    public static function downloadPayslip(Payslip $payslip)
    {
        $payslip->load(['staff.user', 'payRun']);

        return Pdf::loadView('payroll.payslip-pdf', compact('payslip'))
            ->download('payslip-'.$payslip->staff->employee_id.'-'.$payslip->payRun->period_start->format('Y-m').'.pdf');
    }

    // --- Rates -------------------------------------------------------------------------

    public function settings(PayrollSettings $settings)
    {
        return view('admin.payroll.settings', ['settings' => $settings->all(), 'defaults' => PayrollSettings::DEFAULTS]);
    }

    public function updateSettings(PayrollUpdateSettingsRequest $request, PayrollSettings $settings)
    {
        $data = $request->validated();

        $bands = array_values(array_map(fn ($b) => [isset($b['width']) && $b['width'] !== '' ? (float) $b['width'] : null, (float) $b['rate']], $data['bands']));
        // Only the last band may be open-ended.
        foreach ($bands as $i => $band) {
            if ($band[0] === null && $i !== count($bands) - 1) {
                return back()->withErrors(['bands' => 'Only the last band can be left without a width.'])->withInput();
            }
        }
        $bands[count($bands) - 1][0] = null;

        $settings->save([
            'paye_bands' => $bands,
            'rent_relief_rate' => (float) $data['rent_relief_rate'],
            'rent_relief_cap' => (float) $data['rent_relief_cap'],
            'pension_employee_rate' => (float) $data['pension_employee_rate'],
            'pension_employer_rate' => (float) $data['pension_employer_rate'],
            'nhf_rate' => (float) $data['nhf_rate'],
            'working_days_per_month' => (int) $data['working_days_per_month'],
        ]);

        return back()->with('success', 'Payroll rates saved. They apply to pay runs calculated from now on.');
    }

    // --- Per staff member ------------------------------------------------------------------

    public function updateSalary(PayrollUpdateSalaryRequest $request, User $user)
    {
        $staff = $this->staffOf($user);
        $data = $request->validated();

        SalaryStructure::updateOrCreate(['staff_id' => $staff->id], [
            'basic' => $data['basic'],
            'housing' => $data['housing'] ?? 0,
            'transport' => $data['transport'] ?? 0,
            'other_allowance' => $data['other_allowance'] ?? 0,
            'overtime_rate' => $data['overtime_rate'] ?? null,
            'annual_rent' => $data['annual_rent'] ?? null,
            'pension_enabled' => $request->boolean('pension_enabled'),
            'nhf_enabled' => $request->boolean('nhf_enabled'),
            'deduct_absences' => $request->boolean('deduct_absences'),
        ]);
        $staff->update(collect($data)->only(['bank_name', 'bank_account_name', 'bank_account_number', 'tax_id', 'pension_pin'])->all());

        return back()->with('success', 'Pay details saved');
    }

    public function addDeduction(PayrollAddDeductionRequest $request, User $user)
    {
        $staff = $this->staffOf($user);
        $data = $request->validated();

        $staff->deductions()->create($data + ['is_active' => true]);

        return back()->with('success', 'Deduction added');
    }

    public function stopDeduction(User $user, StaffDeduction $deduction)
    {
        abort_unless($deduction->staff_id === $this->staffOf($user)->id, 404);
        $deduction->update(['is_active' => false]);

        return back()->with('success', 'Deduction stopped');
    }

    private function staffOf(User $user): Staff
    {
        abort_unless($user->hasRole('staff') && $user->staff, 404);

        return $user->staff;
    }
}
