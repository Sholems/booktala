<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityMaintenanceCompleteServiceRequest;
use App\Http\Requests\Admin\FacilityMaintenanceStoreGeneratorLogRequest;
use App\Http\Requests\Admin\FacilityMaintenanceStoreRequest;
use App\Models\FacilityMaintenanceSchedule;
use App\Models\GeneratorFuelLog;
use App\Models\Property;
use App\Models\Site;
use Illuminate\Http\Request;

class FacilityMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $equipmentType = $request->query('equipment_type');
        $siteId = $request->query('site_id');

        $schedules = FacilityMaintenanceSchedule::with(['site', 'property'])
            ->when($equipmentType, fn ($q) => $q->where('equipment_type', $equipmentType))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->orderBy('next_due_on')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_active' => FacilityMaintenanceSchedule::where('status', 'active')->count(),
            'overdue' => FacilityMaintenanceSchedule::where('status', 'active')->whereDate('next_due_on', '<', today())->count(),
            'due_soon' => FacilityMaintenanceSchedule::where('status', 'active')->whereBetween('next_due_on', [today(), today()->addDays(7)])->count(),
            'estimated_monthly' => FacilityMaintenanceSchedule::where('status', 'active')->sum('estimated_cost'),
        ];

        $sites = Site::active()->orderBy('name')->get();
        $properties = Property::active()->orderBy('title')->get();
        $equipmentTypes = FacilityMaintenanceSchedule::equipmentTypes();

        return view('admin.facilities.index', compact('schedules', 'stats', 'sites', 'properties', 'equipmentTypes', 'equipmentType', 'siteId'));
    }

    public function store(FacilityMaintenanceStoreRequest $request)
    {
        $validated = $request->validated();

        FacilityMaintenanceSchedule::create($validated + [
            'status' => 'active',
            'estimated_cost' => $validated['estimated_cost'] ?? 0,
        ]);

        return back()->with('success', "Preventative maintenance schedule '{$validated['title']}' created.");
    }

    public function completeService(FacilityMaintenanceCompleteServiceRequest $request, FacilityMaintenanceSchedule $schedule)
    {
        $validated = $request->validated();

        $schedule->recordServiceCompleted($validated['service_notes'] ?? null, $validated['actual_cost'] ?? null);

        return back()->with('success', "Maintenance routine '{$schedule->title}' marked complete. Next service scheduled for {$schedule->next_due_on->format('M j, Y')}.");
    }

    public function generatorLogs(Request $request)
    {
        $siteId = $request->query('site_id');

        $logs = GeneratorFuelLog::with(['site', 'loggedBy'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->latest('logged_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_run_hours' => GeneratorFuelLog::sum('hours_run'),
            'total_diesel_litres' => GeneratorFuelLog::sum('litres_added'),
            'total_diesel_cost' => GeneratorFuelLog::sum('diesel_cost'),
            'avg_consumption' => GeneratorFuelLog::where('hours_run', '>', 0)->count() > 0
                ? round(GeneratorFuelLog::sum('litres_added') / max(1, GeneratorFuelLog::sum('hours_run')), 2)
                : 0,
        ];

        $sites = Site::active()->orderBy('name')->get();

        return view('admin.facilities.generator-logs', compact('logs', 'stats', 'sites', 'siteId'));
    }

    public function storeGeneratorLog(FacilityMaintenanceStoreGeneratorLogRequest $request)
    {
        $validated = $request->validated();

        $hoursRun = max(0, (float) $validated['run_hours_end'] - (float) $validated['run_hours_start']);

        GeneratorFuelLog::create($validated + [
            'hours_run' => $hoursRun,
            'logged_by' => $request->user()->id,
        ]);

        return back()->with('success', "Diesel log recorded: {$hoursRun} run hours, {$validated['litres_added']}L diesel added (₦".number_format($validated['diesel_cost'], 2).').');
    }
}
