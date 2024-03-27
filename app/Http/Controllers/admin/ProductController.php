<?php

namespace App\Http\Controllers\admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Supplier;
use App\Units;
use App\Category;
use App\Brands;
use App\Products;
use App\Stock;
use Picqer;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Cart;
use App\Exports\ProductsExport;
use App\promocode;
use App\promotion;
use App\SubCategory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{

    public function productList()
    {
        if (request()->ajax()) {
            $csrfToken = csrf_token();
            $products = Products::with('categoryInfo')
                ->select('id', 'image', 'name', 'code', 'brand', 'category', 'purchase_price', 'sell_price', 'unit', 'alert_qty')
                ->get();

            foreach ($products as $product) {
                $product->stock = StockController::stock($product->id);
                $stock = (int)$product->stock;
                if ($stock < $product->alert_qty && $stock > 0) {
                    $product->stock_html = '<p class="badge badge-warning">' . $stock . '</p>';
                } elseif ($stock <= 0) {
                    $product->stock_html = '<p class="badge badge-danger">' . $stock . '</p>';
                } else {
                    $product->stock_html = '<p class="badge bg_secondary_teal">' . $stock . '</p>';
                }

                $deleteModalHtml = '
                    <div class="del-modal modal-' . $product->id . ' text-left">
                        <p><b>Record delete confirmation.</b></p>
                        <p>Are you sure you want to delete this record?</p>
                        <button class="btn bg_p_primary py-1 del-close" style="background-color: #808080a6;border-color: #808080a6;">Cancel</button>
                        <form method="post" action="' . route('admin.product.deleteProduct') . '" style="float:right;">
                            <input type="hidden" name="_token" value="' . $csrfToken . '">
                            <input type="hidden" name="id" value="' . $product->id . '">
                            <button type="submit" class="btn bg_secondary_grey py-1">Confirm</button>
                        </form>
                    </div>
                    <script>
                        $(document).ready(function() {
                            $(".btn-delete-' . $product->id . '").click(function() {
                                $(".modal-' . $product->id . '").show("fadeOut");
                            });

                            $(".del-close").click(function() {
                                $(".del-modal").hide("fadeIn");
                            });
                        });
                    </script>
                ';

                $buttonsHtml = '
                    <div class="button-container" style="text-align: center;">
                        <p class="btn bg_secondary_teal p-1 px-2 mb-0 productDetails" style="font-size: 13px;cursor:pointer;" title="product Details" data-pro_id="' . $product->id . '"><i class="fa-fw fa fa-eye"></i></p>
                        <p class="btn bg_p_primary p-1 mb-0 px-2 edit-product" data-productid="' . $product->id . '" style="font-size: 13px;cursor:pointer;" title="Edit product"><i class="fa fa-edit"></i></p>
                        <p class="btn bg_secondary_grey mb-0 p-1 px-2 del-btn btn-delete-' . $product->id . '" data-store_id="' . $product->id . '" style="font-size: 13px;relative;cursor:pointer;" title="Delete product"><i class="fa fa-trash"></i></p>
                    </div>
                ';

                $product->action_buttons_html = $buttonsHtml;
                $product->delete_modal_html = $deleteModalHtml;
            }

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('category', function ($product) {
                    return $product->categoryInfo['name'];
                })
                ->addColumn('stock', function ($product) {
                    return $product->stock_html;
                })
                ->addColumn('image', function ($product) {
                    $imageUrl = $product->image ? asset($product->image) : asset('admin/defaultIcon/no_image.png');
                    $img = '<img src="' . $imageUrl . '" alt="Product Image" class="img-rounded" style="width:35px;height:35px;">';
                    return $img;
                })
                ->addColumn('actions', function ($product) {
                    return $product->action_buttons_html . $product->delete_modal_html;
                })
                ->escapeColumns([])
                ->make(true);
        }

        $suppliers = Supplier::all();
        $units = Units::all();
        $categories = Category::all();
        $brands = Brands::all();
        $productCode = DB::table('systems')->where('id', '1')->value('productCode');
        $products = Products::paginate(10);
        $lastId = Products::orderBy('id', 'desc')->take(1)->first();
        $lastId = $lastId->id + 1;

        // dd($dataTable);
        return view('admin.modules.product.productlists')->with([
            'suppliers' => $suppliers,
            'units' => $units,
            'categories' => $categories,
            'brands' => $brands,
            'productCode' => $productCode,
            'products' => $products,
            'lastId' => $lastId,
        ]);
    }



    public function productAddForm()
    {
        $suppliers = Supplier::all();
        $units = Units::all();
        $categories = Category::all();
        $brands = Brands::all();
        $products = Products::all();
        $lastId = Products::orderBy('id', 'desc')->take(1)->first();
        $lastId = $lastId->id + 1;
        $productCode = DB::table('systems')->where('id', '1')->value('productCode');
        return view('admin.modules.product.productAddForm')->with([
            'suppliers' => $suppliers,
            'units' => $units,
            'categories' => $categories,
            'brands' => $brands,
            'productCode' => $productCode,
            'products' => $products,
            'lastId' => $lastId,
        ]);
    }
    protected function imageUpload($request)
    {
        $productImage = $request->file('image');
        $imageName = $productImage->getClientOriginalName();
        $directory = 'uploads/product_image/';
        $imageUrl = $directory . $imageName;

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        Image::make($productImage)->resize(80, 80)->save($imageUrl);

        return $imageUrl;
    }
    public function productSave(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:products',
            'purchase_price' => 'required',
            'sell_price' => 'required',
            'unit' => 'required',
        ]);

        if ($request->file('image') !== null) {
            $image = $this->imageUpload($request);
        } else {
            $image = null;
        }

        $product = new Products;
        $product->name = $request->name;
        $product->code = $request->code;
        $product->slug = str::slug($request->name);
        $product->supplier = $request->supplier;
        $product->unit = $request->unit;
        $product->brand = $request->brand;
        $product->start_inventory = $request->start_inventory;
        $product->start_cost = $request->start_inventory * $request->purchase_price;
        $product->category = $request->category;
        $product->subcategory = $request->subcategory;
        $product->purchase_price = $request->purchase_price;
        $product->alert_qty = $request->alert_qty;
        $product->sell_price = $request->sell_price;
        $product->whole_sell = $request->whole_sell;
        $product->description = $request->description;
        $product->image = $image;
        try {
            $product->save();
            $proId = $product->id;
            $stock = new Stock;
            $stock->pro_id = $proId;
            $stock->stock = 0;
            $stock->save();
            Toastr::success('Product Added Successfully.');
            return redirect()->route('admin.productList');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }
    public function printBarcode()
    {
        $products = Products::all();

        return view('admin.modules.product.printBarcode')->with([
            'products' => $products
        ]);
    }

    public function generateBarcode(Request $request)
    {
        $request->validate([
            'proid' => 'required',
            'qty' => 'required',
        ]);

        $product = Products::where('id', $request->proid)->first();
        $code = $request->proid;

        $redColor = [255, 0, 0];


        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($code, $generator::TYPE_CODE_128, 3, 40);
        $products = Products::all();

        if ($request->siteName == "siteName") {
            $siteName = DB::table('systems')->where('id', '1')->value('siteName');
        } else {
            $siteName = "";
        }
        if ($request->productname == "productName") {
            $productName = $product->name;
        } else {
            $productName = "";
        }

        if ($request->sellPrice == "sellPrice") {
            $productPrice = "PRICE " . number_format($product->sell_price) . "TK";
        } else {
            $productPrice = "";
        }
        if ($request->label == "label") {
            $proid = sprintf("%08d", $request->proid);
        } else {
            $proid = "";
        }

        return view('admin.modules.product.barcode')->with([
            'qty' => $request->qty,
            'barcode' => $barcode,
            'proid' => $proid,
            'productName' => $productName,
            'productPrice' => $productPrice,
            'siteName' => $siteName,
        ]);
    }
    //get product details by id
    public function productDetails(Request $request)
    {
        $id = $request->pro_id;
        $productInfo = DB::table('products')
            ->leftjoin('categories', 'categories.id', '=', 'products.category')
            ->leftjoin('brands', 'brands.id', '=', 'products.brand')
            ->leftjoin('units', 'units.id', '=', 'products.unit')
            ->select('products.*', 'categories.name as catName', 'brands.name as bName', 'units.name as uName', 'units.id as unit')
            ->where('products.id', $id)->first();

        $totalPurchase = DB::table('purchase_product_lists')->where('pro_id', $id)->sum('qty');
        $totalsale = DB::table('sales_products')->where('pro_id', $id)->sum('qty');
        $start_inventory = DB::table('products')->where('id', $id)->value('start_inventory');
        $totalProduct = $totalPurchase + $start_inventory;
        $inStock = $totalProduct - $totalsale;

        return view('admin.modules.product.productDetails')->with([
            'productInfo' => $productInfo,
            'totalPurchase' => $totalPurchase,
            'totalsale' => $totalsale,
            'inStock' => $inStock,
        ]);
    }
    public function quantityAdjustment()
    {
        return view('admin.modules.product.quantityAdjustment');
    }
    //edit product
    public function productEdit(Request $request)
    {
        $units = Units::all();
        $product = DB::table('products')->where('id', $request->productid)->first();
        return view('admin.modules.product.editProduct')->with([
            'product' => $product,
            'units' => $units,
        ]);
    }
    //update product information
    public function updateProduct(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'purchase_price' => 'required|numeric',
            'sell_price' => 'required|numeric'
        ]);
        $product_check = DB::table('products')->where('id', $request->id)->first();
        // dd($product_check);
        if ($request->file('image') !== null) {
            if (File::exists($product_check->image)) {
                File::delete($product_check->image);
            }
            $image = $this->imageUpload($request);
        } else {
            $image = DB::table('products')->where('id', $request->id)->value('image');
        }
        // try{
        DB::table('products')->where('id', $request->id)
            ->update([
                'name' => $request->name,
                'purchase_price' => $request->purchase_price,
                'sell_price' => $request->sell_price,
                'whole_sell' => $request->whole_sell,
                'description' => $request->description,
                'image' => $image,
                'alert_qty' => $request->alert_qty,
                'start_inventory' => $request->start_inventory,
                'unit' => $request->unit,
            ]);
        Toastr::success('Product  Updated Successfully.');
        return redirect()->back();
        // }catch(\Exception $e)
        // {
        //    Toastr::error('something wrong.');
        //    return redirect()->back();
        // }

    }

    //search product
    public function searchProduct(Request $request)
    {
        $products = DB::table('products')
            ->where('id', 'like', '%' . $request->key . '%')
            ->orWhere('name', 'like', '%' . $request->key . '%')
            ->orWhere('code', 'like', '%' . $request->key . '%')
            ->orWhere('description', 'like', '%' . $request->key . '%')
            ->limit(10)
            ->get();

        if (!$products->isEmpty()) {
            foreach ($products as $product) {
                echo "<a href='product-info/" . $product->id . "' class='list-group-item list-group-item-action mx-0 py-2 productDetails' data-pro_id='" . $product->id . "'>" . $product->name . "(" . $product->code . ")</a>";
            }
        } else {
            echo "No product found.";
        }
    }
    //product info
    public function productInfo($id)
    {
        $units = Units::all();
        $productInfo = DB::table('products')
            ->leftjoin('categories', 'categories.id', '=', 'products.category')
            ->leftjoin('brands', 'brands.id', '=', 'products.brand')
            ->leftjoin('units', 'units.id', '=', 'products.unit')
            ->select('products.*', 'categories.name as catName', 'brands.name as bName', 'units.name as uName', 'units.id as unit')
            ->where('products.id', $id)->first();

        $totalPurchase = DB::table('purchase_product_lists')->where('pro_id', $id)->sum('qty');
        $totalsale = DB::table('sales_products')->where('pro_id', $id)->sum('qty');
        $start_inventory = DB::table('products')->where('id', $id)->value('start_inventory');
        $totalProduct = $totalPurchase + $start_inventory;
        $inStock = $totalProduct - $totalsale;

        return view('admin.modules.product.singleProduct')->with([
            'productInfo' => $productInfo,
            'totalPurchase' => $totalPurchase,
            'totalsale' => $totalsale,
            'inStock' => $inStock,
            'units' => $units,
        ]);
    }

    //delete product
    public function deleteProduct(Request $request)
    {
        $request->validate(
            [
                'id' => 'required|numeric'
            ]
        );
        try {
            $product_check = DB::table('products')->where('id', $request->id)->first();
            if (File::exists($product_check->image)) {
                File::delete($product_check->image);
            }
            DB::table('products')->where('id', $request->id)->delete();
            Toastr::success('product deleted successfully');
            return redirect()->route('admin.productList');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }

    //add low stock product to purchase
    public function addStock($pro_id)
    {
        $product_name = DB::table('products')->where('id', $pro_id)->value('name');
        $product_id = DB::table('products')->where('id', $pro_id)->value('id');
        $purchase_price = DB::table('products')->where('id', $pro_id)->value('purchase_price');
        Cart::add($product_id, $product_name, 1, $purchase_price);
        return redirect()->route('admin.purchaseAdd');
    }

    //export excel product
    public function ProductExcel()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }




    //promotion  Module ADD
    public function promotionadd()
    {
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $products = Products::all();
        // dd($subcategories);
        return view('admin.modules.promotion.add_promotion')->with([
            'categories' => $categories,
            'subcategories' => $subcategories,
            'products' => $products
        ]);
    }

    public function promotionSave(Request $request)
    {
        $request->validate([
            'status'                     => 'required',
            'promotion_name'             => 'required',
            'promotion_category_name'    => '',
            'promotion_subcategory_name' => 'required',
            'promotion_start_duration'   => 'required',
            'promotion_end_duration'     => 'required',
            'promotion_ammount'          => 'required',
            'Promotion_product'          => 'required',
            'Promotion_product_code'     => 'required',

        ]);


        $promotion = new promotion();
        $promotion->status                      = $request->status;
        $promotion->promotion_name              = $request->promotion_name;
        $promotion->Promotion_product_code      = $request->Promotion_product_code;
        $promotion->promotion_category_name     = $request->promotion_category_name;
        $promotion->promotion_subcategory_name  = $request->promotion_subcategory_name;
        $promotion->promotion_start_duration    = $request->promotion_start_duration;
        $promotion->promotion_end_duration      = $request->promotion_end_duration;
        $promotion->promotion_ammount           = $request->promotion_ammount;
        $promotion->Promotion_product           = $request->Promotion_product;
        $promotion->Promotion_product_code      = $request->Promotion_product_code;


        try {
            $promotion->save();
            Toastr::success('Promotion Added Successfully.');
            return redirect()->route('admin.product.promotionlist');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }

    public function promotionlist()
    {

        $promotions = promotion::all();

        return view('admin.modules.promotion.lists_promostion')->with([
            'promotions' => $promotions,
        ]);
    }

    public function deleteProomotion(Request $request)
    {
        $request->validate(
            [
                'id' => 'required|numeric'
            ]
        );
        try {
            $product_check = DB::table('promotions')->where('id', $request->id)->first();
            DB::table('promotions')->where('id', $request->id)->delete();
            Toastr::success('promotion deleted successfully');
            return redirect()->route('admin.product.promotionlist');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }
    //edit promotion
    public function PromotionEdit(Request $request)
    {
        // return "hello";
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $products = Products::all();
        $promotion = DB::table('promotions')->where('id', $request->promotionid)->first();
        return view('admin.modules.promotion.Editpromotion')->with([
            'categories' => $categories,
            'subcategories' => $subcategories,
            'products' => $products,
            'promotion' => $promotion,
        ]);
    }
    //update product information
    public function updatePromotion(Request $request)
    {
        $request->validate([
            'status'                     => 'required',
            'promotion_name'             => 'required',
            'promotion_category_name'    => '',
            'promotion_subcategory_name' => 'required',
            'promotion_start_duration'   => 'required',
            'promotion_end_duration'     => 'required',
            'promotion_ammount'          => 'required',
            'Promotion_product'          => 'required',
            'Promotion_product_code'     => 'required',

        ]);
        $product_check = DB::table('promotions')->where('id', $request->id)->first();

        // try{
        DB::table('promotions')->where('id', $request->id)
            ->update([
                'promotion_name'             =>  $request->promotion_name,
                'Promotion_product_code'     => $request->Promotion_product_code,
                'promotion_category_name'    =>  $request->promotion_category_name,
                'promotion_subcategory_name' => $request->promotion_subcategory_name,
                'promotion_start_duration'   => $request->promotion_start_duration,
                'promotion_end_duration'     => $request->promotion_end_duration,
                'promotion_ammount'          => $request->promotion_ammount,
                'Promotion_product'          => $request->Promotion_product,
                'Promotion_product_code'     => $request->Promotion_product_code,
                'status'                     => $request->status,

            ]);
        Toastr::success('promotions  Updated Successfully.');
        return redirect()->back();
    }


    //promo Code  Module ADD
    public function promoCodeadd()
    {
        return view('admin.modules.promoCode.addpromoCode');
    }

    public function promoCodeSave(Request $request)
    {
        $request->validate([
            'status'                   => 'required',
            'name'                     => 'required',
            'discount'                 => 'required',
            'promocode_start_duration' => 'required',
            'promocode_end_duration'   => 'required',


        ]);

        $pomocode = new promocode();
        $pomocode->status                     = $request->status;
        $pomocode->name                       = $request->name;
        $pomocode->discount                   = $request->discount;
        $pomocode->promocode_start_duration   = $request->promocode_start_duration;
        $pomocode->promocode_end_duration     = $request->promocode_end_duration;

        try {
            $pomocode->save();
            Toastr::success('Promo Code Added Successfully.');
            return redirect()->route('admin.product.promoCodelist');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }
    public function promoCodelist()
    {

        $promocode = promocode::all();

        return view('admin.modules.promoCode.list_promocode')->with([
            'promocode' => $promocode,
        ]);
    }
    public function deletepromoCode(Request $request)
    {
        $request->validate(
            [
                'id' => 'required|numeric'
            ]
        );
        try {
            $product_check = DB::table('promocodes')->where('id', $request->id)->first();
            DB::table('promocodes')->where('id', $request->id)->delete();
            Toastr::success('promo code deleted successfully');
            return redirect()->route('admin.product.promoCodelist');
        } catch (\Exception $e) {
            session()->flash('error-message', $e->getMessage());
            return redirect()->back();
        }
    }

    //edit promotion
    public function promoCodeEdit(Request $request)
    {

        $promoCode = DB::table('promocodes')->where('id', $request->promotionid)->first();
        return view('admin.modules.promoCode.Editpromocode')->with([
            'promoCode' => $promoCode,

        ]);
    }
    //update product information
    public function updatepromoCode(Request $request)
    {
        $request->validate([
            'status'                   => 'required',
            'name'                     => 'required',
            'discount'                 => 'required',
            'promocode_start_duration' => 'required',
            'promocode_end_duration'   => 'required',


        ]);
        $product_check = DB::table('promocodes')->where('id', $request->id)->first();

        // try{
        DB::table('promocodes')->where('id', $request->id)
            ->update([
                'name'                     =>  $request->name,
                'discount'                 => $request->discount,
                'promocode_start_duration' =>  $request->promocode_start_duration,
                'promocode_end_duration'   => $request->promocode_end_duration,
                'status'                   => $request->status,


            ]);
        Toastr::success('promo Code  Updated Successfully.');
        return redirect()->back();
    }
}
