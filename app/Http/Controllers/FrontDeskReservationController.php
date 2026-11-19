<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidReservationTransition;
use App\Http\Requests\FrontDeskReservationGuestsRequest;
use App\Http\Requests\FrontDeskReservationQuoteRequest;
use App\Http\Requests\StoreFrontDeskReservationRequest;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Unit;
use App\Services\FrontDeskBookingService;
use App\Services\GuestService;
use App\Services\PricingService;
use App\Support\PropertyAccess;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function receiptPdf(Request $request, Reservation $reservation)
    {
        PropertyAccess::authorize($request->user(), $reservation->property_id);
        $reservation->load(['unit.site', 'guest', 'payments.collector', 'invoice.items', 'createdBy']);
        $invoice = $reservation->invoice->firstOrFail();

        $siteName = Setting::get('site_name', config('brand.name'));
        $siteEmail = Setting::get('email', config('brand.email'));
        $sitePhone = Setting::get('phone', config('brand.phone'));
        $siteAddress = Setting::get('address', config('brand.address'));
        $companyName = Setting::get('company_name', 'G-Briel Property Ltd');
        $bankName = Setting::get('bank_name', 'First Bank of Nigeria / Providus Bank');
        $accountNumber = Setting::get('account_number', '0123456789');
        $accountName = Setting::get('account_name', 'BookTala (G-Briel Property Ltd)');

        $logoPath = public_path('images/booktala-logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('frontdesk.receipt-pdf', compact(
            'reservation',
            'invoice',
            'siteName',
            'siteEmail',
            'sitePhone',
            'siteAddress',
            'companyName',
            'bankName',
            'accountNumber',
            'accountName',
            'logoBase64'
        ));

        return $pdf->download('receipt-'.$reservation->booking_ref.'.pdf');
    }

    public function quote(FrontDeskReservationQuoteRequest $request, PricingService $pricing): JsonResponse
    {
        $data = $request->validated();
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

    public function guests(FrontDeskReservationGuestsRequest $request, GuestService $guests): JsonResponse
    {
        $data = $request->validated();
        $matches = $guests->matchForStaff($request->user(), $data)->guests;

        return response()->json(['guests' => $matches->map(fn ($guest) => [
            'id' => $guest->id,
            'name' => $guest->name,
            'email' => $guest->email,
            'phone' => $guest->phone,
        ])->values()]);
    }
}
