<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;

/**
 * A staff member's own payslips, once head office has approved the pay run.
 */
class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $payslips = Payslip::where('staff_id', $request->user()->staff?->id)
            ->whereHas('payRun', fn ($q) => $q->whereIn('status', ['approved', 'paid']))
            ->with('payRun')
            ->get()
            ->sortByDesc(fn ($p) => $p->payRun->period_start);

        return view('staff.payslips.index', compact('payslips'));
    }

    public function show(Request $request, Payslip $payslip)
    {
        $this->ensureOwn($request, $payslip);
        $payslip->load(['payRun', 'staff.user']);

        return view('staff.payslips.show', compact('payslip'));
    }

    public function pdf(Request $request, Payslip $payslip)
    {
        $this->ensureOwn($request, $payslip);

        return PayrollController::downloadPayslip($payslip);
    }

    private function ensureOwn(Request $request, Payslip $payslip): void
    {
        abort_unless(
            $payslip->staff_id === $request->user()->staff?->id && in_array($payslip->payRun->status, ['approved', 'paid'], true),
            404
        );
    }
}
