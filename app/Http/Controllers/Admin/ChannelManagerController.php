<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChannelMappingRequest;
use App\Models\OtaChannelMapping;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Services\Ota\OtaSyncService;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;

class ChannelManagerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isHotelMode = SystemSetting::get('hotel_mode', false);

        $unitQuery = PropertyAccess::seesAll($user)
            ? Unit::query()->active()->orderBy('title')
            : Unit::query()->active()->whereIn('id', PropertyAccess::visiblePropertyIds($user))->orderBy('title');

        $units = $unitQuery->with('site')->get();
        $unitIds = $units->pluck('id')->all();

        $mappings = OtaChannelMapping::with(['unit.site'])
            ->whereIn('property_id', $unitIds)
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('property_id', $request->integer('unit_id')))
            ->orderBy('property_id')
            ->get();

        $conflictsCount = $mappings->where('sync_status', 'conflict')->count();
        $activeChannelsCount = $mappings->where('is_active', true)->pluck('channel')->unique()->count();

        $channelsMeta = OtaChannelMapping::CHANNELS;

        return view('admin.channel-manager.index', compact(
            'units',
            'mappings',
            'channelsMeta',
            'conflictsCount',
            'activeChannelsCount',
            'isHotelMode'
        ));
    }

    public function storeMapping(StoreChannelMappingRequest $request)
    {
        $data = $request->validated();

        PropertyAccess::authorize($request->user(), $data['property_id']);

        $mapping = OtaChannelMapping::updateOrCreate(
            [
                'property_id' => $data['property_id'],
                'channel' => $data['channel'],
            ],
            [
                'import_ical_url' => $data['import_ical_url'] ?? null,
                'rate_multiplier' => $data['rate_multiplier'] ?? 1.00,
                'min_stay_override' => $data['min_stay_override'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('success', "Channel connection configured for {$mapping->channel_meta['name']}.");
    }

    public function toggleMapping(OtaChannelMapping $mapping, Request $request)
    {
        PropertyAccess::authorize($request->user(), $mapping->property_id);

        $mapping->update(['is_active' => ! $mapping->is_active]);

        return back()->with('success', "{$mapping->channel_meta['name']} connection ".($mapping->is_active ? 'activated' : 'deactivated').'.');
    }

    public function syncMapping(OtaChannelMapping $mapping, Request $request, OtaSyncService $syncService)
    {
        PropertyAccess::authorize($request->user(), $mapping->property_id);

        if (empty($mapping->import_ical_url)) {
            return back()->with('error', 'Cannot sync: no import iCal URL configured.');
        }

        try {
            $res = $syncService->syncMapping($mapping);
            $msg = "Synced {$mapping->channel_meta['name']}: {$res['imported']} new bookings imported, {$res['updated']} updated, {$res['existing']} unchanged.";
            if ($res['conflicts'] > 0) {
                $msg .= " Warning: {$res['conflicts']} booking conflicts prevented.";
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync failed: '.$e->getMessage());
        }
    }

    public function syncAll(Request $request, OtaSyncService $syncService)
    {
        $res = $syncService->syncAll();

        $msg = "OTA Sync All complete: {$res['imported']} imported, {$res['updated']} updated, {$res['existing']} unchanged across {$res['total_channels']} channel feeds.";
        if ($res['conflicts'] > 0) {
            $msg .= " ({$res['conflicts']} conflicts blocked)";
        }
        if ($res['errors'] > 0) {
            $msg .= " ({$res['errors']} feeds had network/HTTP errors)";
        }

        return back()->with('success', $msg);
    }

    public function destroyMapping(OtaChannelMapping $mapping, Request $request)
    {
        PropertyAccess::authorize($request->user(), $mapping->property_id);

        $mapping->delete();

        return back()->with('success', 'Channel connection removed.');
    }
}
