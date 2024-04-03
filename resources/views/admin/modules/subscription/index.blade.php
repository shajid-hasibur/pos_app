@extends('admin.layouts.adminmaster')

@section('adminTitle')
    Attendence
@endsection

@section('adminContent')
    <div class="container d-flex justify-content-center align-items-center mt-5">
        <div class="card m-2" style="width: 18rem;">
            <div class="card-body">
                <form action="{{ route('admin.saas.product.payment') }}" method="post">
                    @csrf
                    <h5 class="card-title">Monthly Subscription</h5>
                    <h6 class="card-subtitle mb-2 text-muted">1000 BDT per month</h6>
                    <input type="hidden" value="1000" name="product_price">
                    <input type="hidden" value="Monthly Subscription" name="product_name">
                    <button class="btn btn-primary" type="submit">Subscribe Now</button>
                </form>
            </div>
        </div>
        <div class="card m-2" style="width: 18rem;">
            <div class="card-body">
                <form action="{{ route('admin.saas.product.payment') }}" method="post">
                    @csrf
                    <h5 class="card-title">Yearly Subscription</h5>
                    <h6 class="card-subtitle mb-2 text-muted">10000 BDT per year</h6>
                    <input type="hidden" value="10000" name="product_price">
                    <input type="hidden" value="Yearly Subscription" name="product_name">
                    <button class="btn btn-primary" type="submit">Subscribe Now</button>
                </form>
            </div>
        </div>

    </div>
    <div class="container d-flex justify-content-center align-items-center mt-5">
        <button class="btn btn-secondary" type="submit">Check Your Package</button>
    </div>
@endsection
