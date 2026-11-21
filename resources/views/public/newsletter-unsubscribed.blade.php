@extends('layouts.app')

@section('title', 'Email Subscription Preferences · BookTala')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-slate-50">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8 text-center">
        <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
            <i class="fas fa-envelope-open-text"></i>
        </div>

        <h1 class="text-2xl font-serif font-bold text-slate-900 mb-2">You Have Been Unsubscribed</h1>
        <p class="text-slate-600 text-sm mb-6 leading-relaxed">
            @if($subscriber)
                <strong>{{ $subscriber->email }}</strong> has been removed from our marketing and promotional newsletters. You will no longer receive marketing updates from BookTala.
            @else
                Your email has been removed from our marketing mailing lists.
            @endif
        </p>

        @if(session('status'))
            <div class="p-3 bg-emerald-50 text-emerald-700 text-sm font-medium rounded-xl mb-6 border border-emerald-200">
                <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
            </div>
        @endif

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-500 mb-6 text-left space-y-1">
            <p class="font-semibold text-slate-700">Important Note:</p>
            <p>You may still receive essential transactional emails, such as direct booking confirmations, receipt invoices, and account security notifications when you make reservations.</p>
        </div>

        <div class="space-y-3">
            @if($subscriber)
                <form action="{{ route('newsletter.resubscribe', ['token' => $token]) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition">
                        Did this by mistake? Resubscribe me
                    </button>
                </form>
            @endif

            <a href="{{ route('home') }}" class="inline-block w-full py-2.5 px-4 bg-navy-950 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl transition shadow-md shadow-navy-950/10" style="background-color: #0B2D5B;">
                Return to BookTala Home
            </a>
        </div>
    </div>
</div>
@endsection
