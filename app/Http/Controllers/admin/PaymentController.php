<?php

namespace App\Http\Controllers\admin;

use App\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\PaymentDetail;
use App\UserSubscription;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.modules.subscription.index');
    }

    public function payment(Request $request)
    {
        $check = $this->checkSubscription();

        if ($check == "0") {
            $server = $this->checkServerStatus();

            if ($server['selectedServer'] == true) {
                $userData = [
                    'id' => Auth::user()->id,
                    'customer_name' => Auth::user()->first_name . " " . Auth::user()->last_name,
                    'customer_email' => Auth::user()->email,
                    'customer_address' => Auth::user()->address,
                    'customer_phone' => Auth::user()->mobile,
                    'product_desc' => $request->product_name,
                    'amount' => $request->product_price,
                ];

                $response = $this->initPayment($server, $userData);

                if (isset($response['token'])) {
                    $redirect = 'https://sandbox.walletmix.com/bank-payment-process/' . $response['token'];
                    return redirect($redirect);
                } else {
                    dd($response);
                }
            } else {
                dd('Server is sleeping');
            }
        } else {
            Toastr::warning("You already have an active subscription. Try again after subscription expire.", "Warning", ["positionClass" => "toast-top-center"]);
            return redirect()->back();
        }
    }

    protected function initPayment($server, $data)
    {
        try {
            Cache::put('package-info', $data);
            $url = $server['url'];
            $store_id = config('walletmix.store_id');
            $store_key = config('walletmix.store_key');
            $store_username = config('walletmix.store_username');
            $store_user_password = config('walletmix.store_user_password');
            $authorization = 'Basic ' . base64_encode("$store_username:$store_user_password");
            $options = base64_encode("s=localhost:8000,i=127.0.0.1");

            $order_id = mt_rand(111111, 999999);
            while (UserSubscription::where('order_id', $order_id)->exists()) {
                $order_id = mt_rand(111111, 999999);
            }

            Cache::put('order_id', $order_id);

            $random_refs_id = mt_rand(1111111111, 9999999999);
            while (PaymentDetail::where('merchant_ref_id', $random_refs_id)->exists()) {
                $random_refs_id = mt_rand(1111111111, 9999999999);
            }

            $curl = curl_init();

            $data = [
                'wmx_id' => $store_id,
                'merchant_order_id' => $order_id,
                'merchant_ref_id' => $random_refs_id,
                'app_name' => 'localhost:8000',
                'cart_info' => $store_id . ',http://localhost:8000,MyApp',
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_add' => $data['customer_address'],
                'customer_phone' => $data['customer_phone'],
                'product_desc' => $data['product_desc'],
                'amount' => $data['amount'],
                'currency' => 'BDT',
                'options' => $options,
                'callback_url' =>  route('admin.payment.success'),
                'access_app_key' => $store_key,
                'authorization' => $authorization,
            ];

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
            ));

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                dd('error');
            } else {
                $responseObject = json_decode($response, true);
                return $responseObject;
            }
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }

    public function success(Request $request)
    {
        $response = json_decode($request->merchant_txn_data, true);
        $data = $this->checkPayment($response);

        if ($data['txn_status'] == '1000') {
            $store = $this->storePaymentDetails($data);
            if ($store) {
                $subcription = $this->userSubscription($store);
                Toastr::success("Payment successful you can now use full application.", "Success", ["positionClass" => "toast-top-center"]);
                return redirect()->route('admin.dashboard');
            }
        } elseif ($data['txn_status'] == '1001') {
            Toastr::error("Payment transaction rejected. To use our full system please make a payment.", "Error", ["positionClass" => "toast-top-center"]);
            return redirect()->route('admin.dashboard');
        } elseif ($data['txn_status'] == '1009') {
            Toastr::error("Payment transaction canceled. To use our full system please make a payment.", "Error", ["positionClass" => "toast-top-center"]);
            return redirect()->route('admin.dashboard');
        } else {
            Toastr::error("Payment transaction failed.", "Error", ["positionClass" => "toast-top-center"]);
            return redirect()->route('admin.dashboard');
        }
    }

    protected function checkServerStatus()
    {
        try {
            $url = 'https://sandbox.walletmix.com/check-server';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => '{
                    "type": "json"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $responseObject = json_decode($response, true);
            return $responseObject;
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    protected function checkPayment($response)
    {
        $wmx_id = config('walletmix.store_id');
        $store_username = config('walletmix.store_username');
        $store_user_password = config('walletmix.store_user_password');
        $authorization = 'Basic ' . base64_encode("$store_username:$store_user_password");
        $store_key = config('walletmix.store_key');

        $requestData = [
            'wmx_id' => $wmx_id,
            'authorization' => $authorization,
            'access_app_key' => $store_key,
            'token' => $response['token'],
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://sandbox.walletmix.com/check-payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestData,
        ]);

        $data = curl_exec($curl);
        curl_close($curl);
        $responseArray = json_decode($data, true);
        return $responseArray;
    }

    protected function storePaymentDetails($data)
    {

        $customerDetails = json_decode($data['customer_details'], true);
        $customerPhone = $customerDetails['customer_phone'];
        $userId = Admin::select('id')->where('mobile', $customerPhone)->first()->id;

        $paymentDetail = new PaymentDetail();

        $paymentDetail->ref_id                  = $data['ref_id'];
        $paymentDetail->token                   = $data['token'];
        $paymentDetail->merchant_req_amount     = $data['merchant_req_amount'];
        $paymentDetail->merchant_ref_id         = $data['merchant_ref_id'];
        $paymentDetail->merchant_currency       = $data['merchant_currency'];
        $paymentDetail->merchant_amount_bdt     = $data['merchant_amount_bdt'];
        $paymentDetail->conversion_rate         = $data['conversion_rate'];
        $paymentDetail->service_ratio           = $data['service_ratio'];
        $paymentDetail->wmx_charge_bdt          = $data['wmx_charge_bdt'];
        $paymentDetail->emi_ratio               = $data['emi_ratio'];
        $paymentDetail->emi_charge              = $data['emi_charge'];
        $paymentDetail->bank_amount_bdt         = $data['bank_amount_bdt'];
        $paymentDetail->discount_bdt            = $data['discount_bdt'];
        $paymentDetail->merchant_order_id       = $data['merchant_order_id'];
        $paymentDetail->request_ip              = $data['request_ip'];
        $paymentDetail->txn_status              = $data['txn_status'];
        $paymentDetail->extra_json              = $data['extra_json'];
        $paymentDetail->card_details            = $data['card_details'];
        $paymentDetail->is_foreign              = $data['is_foreign'];
        $paymentDetail->payment_card            = $data['payment_card'];
        $paymentDetail->card_code               = $data['card_code'];
        $paymentDetail->payment_method          = $data['payment_method'];
        $paymentDetail->init_time               = $data['init_time'];
        $paymentDetail->txn_time                = $data['txn_time'];
        $paymentDetail->statusCode              = $data['statusCode'];
        $paymentDetail->user_id                 = $userId;

        $paymentDetail->save();
        return $paymentDetail;
    }

    protected function userSubscription($data)
    {
        $days = "";
        $cache = Cache::get('package-info');
        $orderId = Cache::get('order_id');

        if ($cache['product_desc'] === "Monthly Subscription") {
            $days = 30;
        }
        if ($cache['product_desc'] === "Yearly Subscription") {
            $days = 365;
        }

        $current_date_object = Carbon::now();
        $current_date = $current_date_object->toDateString();

        $new_date = $current_date_object->addDays($days);
        $new_date = $new_date->toDateString();

        $userSubscription = new UserSubscription();
        $userSubscription->admin_id = $cache['id'];
        $userSubscription->payment_details_id = $data->id;
        $userSubscription->package_name = $cache['product_desc'];
        $userSubscription->package_price = $cache['amount'];
        $userSubscription->order_id = $orderId;
        $userSubscription->purchase_date = $current_date;
        $userSubscription->subscription_end_date = $new_date;
        $userSubscription->days_left = $days;
        $userSubscription->save();

        Cache::forget('package-info');
        Cache::forget('order_id');
        return $userSubscription;
    }

    protected function checkSubscription()
    {
        $user = Auth::user();
        $subscription = UserSubscription::where('admin_id', $user->id)->first();

        if ($subscription) {
            $end_date = Carbon::parse($subscription->subscription_end_date);
            $current_date = Carbon::now();

            if ($end_date->isAfter($current_date)) {
                return "1";
            } else {
                return "0";
            }
        } else {
            return "0";
        }
    }
}
