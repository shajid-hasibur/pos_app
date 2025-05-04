@extends('admin.layouts.adminmaster')

@section('adminTitle')
    Package-Details
@endsection

@section('adminContent')
    <div class="container justify-content-center align-items-center">
        <div class="row">
            <div class="form-group col-12">
                <div class="card my-5 bg_p_primary">
                    <div class="card-body">
                        <h1 class="text-center">Subscription Information</h1>
                    </div>
                </div>
            </div>
            <div class="form-group col-6">
                <div class="card" style="height: 306px">
                    <div class="card-header bg_p_primary">
                        <h2 class="text-center">User Information</h2>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <p>Name : {{ $user->first_name }} {{ $user->last_name }}</p>
                        <p>E-mail : {{ $user->email }}</p>
                        <p>Mobile : {{ $user->mobile }}</p>
                        <p>Adddress : {{ $user->address }}</p>
                    </div>
                </div>
            </div>
            <div class="form-group col-6">
                <div class="card" style="height: 306px">
                    <div class="card-header bg_p_primary">
                        <h2 class="text-center">Package Information</h2>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">

                        @if (isset($package_info))
                            <p>Order Id : {{ $package_info->order_id }}</p>
                            <p>Package Name : {{ $package_info->package_name }}</p>
                            <p>Package Price : {{ $package_info->package_price }}</p>
                            <p>Purchase Date : {{ $package_info->purchase_date }}</p>
                            <p>Subscription End : {{ $package_info->subscription_end_date }}</p>
                            <p>Days Left : {{ $package_info->days_left }}</p>
                        @else
                            <div class="text-center">
                                <p>No subscription found</p>
                                <a href="{{ route('admin.subscription.product.view') }}" class="btn bg_p_primary">Get
                                    Subscription</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
