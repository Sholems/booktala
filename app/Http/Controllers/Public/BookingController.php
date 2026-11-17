<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(
        private PricingService $pricingService,
        private BookingService $bookingService,
        private PaymentService $paymentService
    ) {}

    public function show(Request $request, $slug)
    {
        $property = Property::where('slug', $slug)->active()->firstOrFail();

        $checkIn = $request->get('check_in');
        $checkOut = $request->get('check_out');
        $guests = $request->get('guests', 1);

        $pricing = null;
        if ($checkIn && $checkOut) {
            try {
                $pricing = $this->pricingService->calculatePrice($property, $checkIn, $checkOut, $guests);
            } catch (\Exception $e) {
                $pricing = null;
            }
        }

        return view('public.booking.show', compact('property', 'pricing'));
    }

    public function process(Request $request)
    {
        $isGuest = !auth()->check();

        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'discount_code' => 'nullable|string',
            'special_requests' => 'nullable|string|max:2000',
            'guest_name' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'guest_email' => [Rule::requiredIf($isGuest), 'nullable', 'email'],
            'guest_phone' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:30'],
            'guest_password' => [Rule::requiredIf($isGuest), 'nullable', 'string'],
        ]);

        $property = Property::active()->findOrFail($request->property_id);
        $user = null;

        // Never trust a client-supplied user_id: the booking owner is either the
        // logged-in user or a guest who proves ownership of the email.
        if (auth()->check()) {
            $user = auth()->user();
        } else {
            $existingUser = \App\Models\User::where('email', $request->guest_email)->first();

            if ($existingUser) {
                if (!Hash::check((string) $request->guest_password, $existingUser->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An account with this email already exists. Please log in or enter the correct password.',
                    ], 422);
                }

                if (!$existingUser->isActive()) {
                    return response()->json(['success' => false, 'message' => 'Your account is not active. Please contact support.'], 422);
                }

                $user = $existingUser;
            } else {
                if (strlen((string) $request->guest_password) < 8) {
                    return response()->json(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
                }

                $user = \App\Models\User::create([
                    'name' => $request->guest_name,
                    'email' => $request->guest_email,
                    'phone' => $request->guest_phone,
                    'password' => Hash::make($request->guest_password),
                    'status' => 'active',
                ]);
                $user->assignRole('customer');
            }

            Auth::login($user);
            $request->session()->regenerate();
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unable to create or find user'], 422);
        }

        try {
            [$reservation, $authorizationUrl] = $this->bookingService->startBooking($user, $property, $request->only([
                'check_in', 'check_out', 'guests', 'discount_code', 'special_requests',
            ]));

            return response()->json([
                'success' => true,
                'reservation_id' => $reservation->id,
                'authorization_url' => $authorizationUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->get('reference') ?: $request->get('trxref');

        if (!$reference) {
            return redirect()->route('home')->with('error', 'Missing payment reference.');
        }

        try {
            $verification = $this->paymentService->verifyPayment($reference);
            $data = $verification['data'] ?? [];

            if (($data['status'] ?? null) === 'success') {
                $reservation = $this->paymentService->markPaymentSuccessful($data);

                if ($reservation) {
                    $request->session()->put('confirmed_booking_ref', $reservation->booking_ref);

                    return redirect()->route('booking.confirmation', ['booking_ref' => $reservation->booking_ref])
                        ->with('success', 'Payment successful! Your booking is confirmed.');
                }
            }

            return redirect()->route('home')->with('error', 'Payment verification failed.');
        } catch (\Exception $e) {
            Log::error('Paystack callback failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            return redirect()->route('home')->with('error', 'An error occurred during payment verification.');
        }
    }

    public function webhook(Request $request)
    {
        return $this->paymentService->handleWebhook(
            $request->getContent(),
            (string) $request->header('x-paystack-signature')
        );
    }

    public function confirmation(Request $request, $booking_ref)
    {
        $reservation = Reservation::where('booking_ref', $booking_ref)
            ->with('property', 'user')
            ->firstOrFail();

        $ownsBooking = auth()->check()
            && (auth()->id() === $reservation->user_id || auth()->user()->isAdmin());

        if (!$ownsBooking && $request->session()->get('confirmed_booking_ref') !== $booking_ref) {
            abort(403);
        }

        return view('public.booking.confirmation', compact('reservation'));
    }
}