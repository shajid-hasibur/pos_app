<?php

namespace App\Http\Controllers\admin;

use App\Admin;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function decrementDays()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $subscription = Admin::with('subscription')->find($user->id)->subscription;

            if ($subscription) {
                $endDate = Carbon::parse($subscription->subscription_end_date);
                $daysLeft = $endDate->diffInDays(Carbon::now());
                $subscription->days_left = max(0, $daysLeft);
                $subscription->save();
            }

            return response()->json(['success' => 'decremented']);
        }
    }
}
