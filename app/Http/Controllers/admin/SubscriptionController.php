<?php

namespace App\Http\Controllers\admin;

use App\Admin;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\UserSubscription;
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

    public function packageDetails(Request $request)
    {
        $user = Auth::user();
        $package_info = UserSubscription::where('admin_id', $user->id)->first();
        return view('admin.modules.subscription.package-details', compact('user', 'package_info'));
    }

    public function expiredPage()
    {
        return view('admin.modules.subscription.expired');
    }
}
