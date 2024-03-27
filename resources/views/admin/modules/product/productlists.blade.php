@extends('admin.layouts.adminmaster')
@section('adminTitle')
    All Products- Admin Dashboard
@endsection
@section('adminContent')
    @php
        use App\Http\Controllers\admin\StockController;
    @endphp
    <style>
        input[type=text]:focus {
            border-color: inherit;
            -webkit-box-shadow: none;
            box-shadow: none;
            height: 28px;
            font-size: inherit;
            border-color: rgba(229, 103, 23, 0.8);
            outline-color: gray;
            font-size: 15px;
            text-transform: none;

        }

        .table td {
            padding-bottom: 0px;
            vertical-align: middle;
        }
    </style>

    <div class="col-md-12 mt-5 pt-3 border-bottom">
        <div class="text-dark px-0">
            <p class="mb-1"><a href="{{ route('admin.dashboard') }}"><i class="fa fa-home"></i> Dashboard / </a><a
                    href="" class="active-slink">Product List</a> <span class="top-date">Total Products:
                    {{ $products->total() }}</span></p>
        </div>
    </div>

    <div class="container-fluid p-3">
        <div class="box">
            <div class="box-header">
                <div class="box-icon-left border-right" style="height:100%">
                    <p class="btn mt-0 task-icon"><i class="fa fa-barcode"></i></p>
                </div>
                <h2 class="blue task-label">All Products</h2>
                <div class="box-icon border-left" style="height:100%">
                    <div class="dropdown mt-0">
                        <p class="task-btn text_p_primary" title="Actions">
                            <i class="fa fa-th-list"></i>
                            </button>
                        <div class="task-menu p-2">
                            <a href="{{ route('admin.productAdd') }}" class="dropdown-item pl-0" type="button">
                                <i class="fa-fw fa fa-plus-circle"></i> Add New Product
                            </a>
                            <a href="{{ route('admin.product.export.excel') }}" class="dropdown-item pl-1" type="button">
                                <i class="fa fa-file-excel"></i> Export Excel
                            </a>
                            <a class="dropdown-item pl-1" type="button">
                                <i class="fa fa-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-content">
                <div class="row">
                    <div class="col-lg-12">
                        <p class="introtext mb-0">Please use the table below to navigate or filter the results. You can
                            download the table as excel and pdf.</p>
                        <div class="row">
                            @if (Session::has('error-message'))
                                <p class="alert alert-danger">{{ Session::get('error-message') }}</p>
                            @endif
                            <div class="col-4 mt-2"></div>
                        </div>
                        <table class="table table-bordered table-hover" id="product-table">
                            <thead class="bg_p_primary">
                                <tr>
                                    <th class="font-weight-bold text-center" scope="col">#</th>
                                    <th class="font-weight-bold text-center" scope="col">Image</th>
                                    <th class="font-weight-bold text-center" scope="col">Name</th>
                                    <th class="font-weight-bold text-center" scope="col">Code</th>
                                    <th class="font-weight-bold text-center" scope="col">Brand</th>
                                    <th class="font-weight-bold text-center" scope="col">Category</th>
                                    <th class="font-weight-bold text-center" scope="col">Cost</th>
                                    <th class="font-weight-bold text-center" scope="col">Price</th>
                                    <th class="font-weight-bold text-center" scope="col">Unit</th>
                                    <th class="font-weight-bold text-center" scope="col">Alert Quantity</th>
                                    <th class="font-weight-bold text-center" scope="col">Stock</th>
                                    <th class="font-weight-bold text-center" scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade bd-example-modal-lg productModal" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content p-3 modal-data">

            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h2 class="modal-title" id="exampleModalLabel">Add New Product</h2>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                <div class="modal-body">
                    <form method="post" action="#" entype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Supplier<i class="fa-fw fa fa-plus-circle"></i></label>
                            <select class="custom-select" name="supplier">
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->company }}({{ $supplier->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="Product Name">
                        </div>
                        <div class="form-group">
                            <label>Product Code *</label>

                            <input type="text" class="form-control" name="code" readonly=""
                                value="{{ $productCode }}-{{ $lastId }}">
                        </div>
                        <div class="form-group">
                            <label>Starting Inventory</label>
                            <input type="number" class="form-control" name="start_inventory"
                                placeholder="Starting Inventory">
                        </div>
                        <div class="form-group">
                            <label>Product Category</label>
                            <select class="custom-select" name="category" id="category">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach

                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Sub Category</label>
                            <select class="custom-select" name="subcategory" id="subcategory">
                                <option value="">Select Subcategory</option>

                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Band</label>
                            <select class="custom-select" name="btand">
                                <option value="">Select Brand</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Unit</label>
                            <select class="custom-select" name="unit">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Cost *</label>
                            <input type="text" class="form-control" name="purchase_price"
                                placeholder="Product Puuchase Price">
                        </div>

                        <div class="form-group">
                            <label>Product Price *</label>
                            <input type="text" class="form-control" name="sell_price"
                                placeholder="Product Sell Price">
                        </div>
                        <div class="form-group">
                            <label>whole sell Price </label>
                            <input type="text" class="form-control" name="whole_sell"
                                placeholder="Product whole sell Price">
                        </div>
                        <div class="form-group">
                            <label>Alert Quantity</label>
                            <input type="text" class="form-control" name="alert_qty" placeholder="Alert Quantity">
                        </div>

                        <div class="form-group">
                            <label>Product Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" class="form-control-file" name="image1">
                        </div>

                </div>
                <div class="modal-footer">
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Add Product">
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#product-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.productList') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'image'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'code'
                    },
                    {
                        data: 'brand'
                    },
                    {
                        data: 'category'
                    },
                    {
                        data: 'purchase_price'
                    },
                    {
                        data: 'sell_price'
                    },
                    {
                        data: 'unit'
                    },
                    {
                        data: 'alert_qty'
                    },
                    {
                        data: 'stock'
                    },
                    {
                        data: 'actions'
                    }
                ],
                columnDefs: [{
                    targets: '_all',
                    className: 'dataTable-text-center',
                    "orderable": false
                }]
            });
            $("#category").on('change', function() {
                var catId = $(this).val();
                //ajax

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ route('admin.subcategory.selectSubcategory') }}",
                    type: "POST",
                    data: {
                        'catId': catId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#subcategory').empty();
                        $.each(data, function(index, subcatObj) {
                            $("#subcategory").append('<option value ="' + subcatObj.id +
                                '">' + subcatObj.name + '</option>');
                        });

                    },
                    error: function() {
                        alert("error ase");
                    }
                });
                //endajax
            });

            //edit product
            $(".edit-product").click(function() {
                var productid = $(this).data('productid');

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ route('admin.product.productEdit') }}",
                    type: "POST",
                    data: {
                        'productid': productid
                    },
                    success: function(data) {
                        $(".modal-data").html(data);
                        $('.productModal').modal('show');
                    },
                    error: function() {
                        toastr.error("Something went Wrong, Please Try again.");
                    }
                });
            });

            $(document).on('click', '.edit-product', function() {
                var productid = $(this).data('productid');

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ route('admin.product.productEdit') }}",
                    type: "POST",
                    data: {
                        'productid': productid
                    },
                    success: function(data) {
                        $(".modal-data").html(data);
                        $('.productModal').modal('show');
                    },
                    error: function() {
                        toastr.error("Something went Wrong, Please Try again.");
                    }
                });
            });

            //product details
            $(document).on('click', '.productDetails', function() {
                var pro_id = $(this).data('pro_id');
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ route('admin.product.productDetails') }}",
                    type: "POST",
                    data: {
                        'pro_id': pro_id
                    },
                    success: function(data) {
                        $(".modal-data").html(data);
                        $('.productModal').modal('show');
                    },
                    error: function() {
                        toastr.error("Something went Wrong, Please Try again.");
                    }
                });
            });

            $(document).on('click', '.del-btn', function() {
                $('.del-modal').modal('open');
            });


        });
    </script>
@endsection
