<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidReservationTransition;
use App\Http\Requests\StoreFrontDeskReservationRequest;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Unit;
use App\Services\FrontDeskBookingService;
use App\Services\GuestService;
use App\Services\PricingService;
use App\Support\PropertyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FrontDeskReservationController extends Controller
{
    public function create(Request $request)
    {
        $sites = Site::query()->whereIn('id', PropertyAccess::activeSiteIds($request->user()))->orderBy('name')->get();

        if (PropertyAccess::seesAll($request->user())) {
            $sites = Site::query()->orderBy('name')->get();
        }

        $units = Unit::query()->visibleTo($request->user())->active()->with('site')->orderBy('title')->get();
        $layout = $request->user()->hasAnyRole(['super_admin', 'admin']) ? 'layouts.admin' : 'layouts.staff';

        return view('admin.reservations.create', compact('sites', 'units', 'layout'));
    }

    public function store(StoreFrontDeskReservationRequest $request, FrontDeskBookingService $bookings): RedirectResponse
    {
        $data = $request->validated();
        abort_unless(PropertyAccess::activeSiteIds($request->user())->where('site_id', $data['site_id'])->exists() || PropertyAccess::seesAll($request->user()), 404);
        $unit = Unit::whereKey($data['unit_id'])->where('site_id', $data['site_id'])->active()->firstOrFail();

        try {
            $reservation = $bookings->create($request->user(), $unit, $data);
        } catch (\DomainException|InvalidReservationTransition $exception) {
            return back()->withInput()->withErrors(['booking' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['booking' => 'The booking could not be recorded. Please try again.']);
        }

        return redirect()->route('frontdesk.reservations.receipt', $reservation)->with(
            'success',
            'Booking '.$reservation->booking_ref.' and its receipt were created successfully.',
        );
    }

    public function receipt(Request $request, Reservation $reservation)
    {
        PropertyAccess::authorize($request->user(), $reservation->property_id);
        $reservation->load(['unit.site', 'guest', 'payments.collector', 'invoice.items', 'createdBy']);
        $invoice = $reservation->invoice->firstOrFail();
        $layout = $request->user()->hasAnyRole(['super_admin', 'admin']) ? 'layouts.admin' : 'layouts.staff';

        return view('frontdesk.receipt', compact('reservation', 'invoice', 'layout'));
    }

    public function quote(Request $request, PricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'unit_id' => ['required', 'integer', 'exists:properties,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'discount_code' => ['nullable', 'string'],
        ]);
        abort_unless(PropertyAccess::activeSiteIds($request->user())->where('site_id', $data['site_id'])->exists() || PropertyAccess::seesAll($request->user()), 404);
        $unit = Unit::whereKey($data['unit_id'])->where('site_id', $data['site_id'])->active()->firstOrFail();

        try {
            $quote = $pricing->calculatePrice(
                $unit,
                $data['check_in'],
                $data['check_out'],
                $data['guests'],
                $data['discount_code'] ?? null,
            );
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages(['unit_id' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['unit_id' => 'The quote could not be calculated. Please try again.']);
        }

        return response()->json($quote);
    }

    public function guests(Request $request, GuestService $guests): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email'],
        ]);
        $matches = $guests->matchForStaff($request->user(), $data)->guests;

        return response()->json(['guests' => $matches->map(fn ($guest) => [
            'id' => $guest->id,
            'name' => $guest->name,
            'email' => $guest->email,
            'phone' => $guest->phone,
        ])->values()]);
    }
}
