<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelSetting;
use App\Models\Property;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class ChannelManagerController extends Controller
{
    public function index()
    {
        $isHotelMode = SystemSetting::get('hotel_mode', false);
        $channels = ChannelSetting::all();
        $properties = Property::active()->get();

        return view('admin.channel-manager.index', compact('channels', 'properties', 'isHotelMode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'channel_name' => 'required|string',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'sync_frequency' => 'nullable|string',
        ]);

        ChannelSetting::updateOrCreate(
            ['channel_name' => $request->channel_name],
            [
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
                'sync_frequency' => $request->sync_frequency ?? 'hourly',
                'property_id' => $request->property_id,
            ]
        );

        return back()->with('success', 'Channel settings updated');
    }

    public function toggle(Request $request, ChannelSetting $channel)
    {
        $channel->update(['is_enabled' => ! $channel->is_enabled]);

        if ($channel->is_enabled) {
            $channel->update(['last_sync' => now()]);
        }

        return back()->with('success', 'Channel '.($channel->is_enabled ? 'enabled' : 'disabled'));
    }

    public function sync(ChannelSetting $channel)
    {
        $channel->update(['last_sync' => now()]);

        return back()->with('success', 'Sync initiated for '.$channel->channel_name);
    }

    public function destroy(ChannelSetting $channel)
    {
        $channel->delete();

        return back()->with('success', 'Channel removed');
    }
}
