<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use App\Category;
use App\SubCategory;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
  public function managesSubCategory()
  {
    $categoryCode = DB::table('systems')->where('id', '1')->value('subCategoryCode');
    $categories = Category::all();
    $subcategories = SubCategory::all();
    return view('admin.modules.setting.subcategory.subcategory')->with(['categories' => $categories, 'categoryCode' => $categoryCode, 'subcategories' => $subcategories]);
  }
  protected function imageUpload($request)
  {
    $productImage = $request->file('image');
    $imageName = $productImage->getClientOriginalName();
    $directory = 'uploads/subcategory_image/';
    $imageUrl = $directory . $imageName;

    if (!file_exists($directory)) {
      mkdir($directory, 0755, true);
    }

    Image::make($productImage)->resize(80, 80)->save($imageUrl);

    return $imageUrl;
  }
  public function saveSubCategory(Request $request)
  {
    $validdata = $request->validate([
      'name' => 'required',
      'code' => 'required',
    ]);
    if ($request->file('image') !== null) {
      $image = $this->imageUpload($request);
    } else {
      $image = null;
    }
    $category = new SubCategory;
    $category->name = $request->name;
    $category->image = $image;
    $category->code = $request->code;
    $category->parentId = $request->parentId;
    $category->description = $request->description;
    $category->slug = str::slug($request->name);

    try {
      $category->save();
      Toastr::success('New Category Added Successfully.');
      return redirect()->route('admin.subcategory');
    } catch (\Exception $e) {
      session()->flash('error-message', $e->getMessage());
      return redirect()->back();
    }
  }

  public function selectSubcategory(Request $request)
  {
    $subcategory = DB::table('sub_categories')->where('parentId', $request->catId)->get();
    return response()->json($subcategory);
  }

  //sub category details
  public function subcategoryDetails(Request $request)
  {
    $category = DB::table('sub_categories')->where('id', $request->id)->first();
    return view('admin.modules.setting.subcategory.subCategoryDetails')->with(['category' => $category]);
  }
  public function editsubCategory(Request $request)
  {

    $category = DB::table('sub_categories')->where('id', $request->id)->first();

    return view('admin.modules.setting.subcategory.editSubCategory')->with(['category' => $category]);
  }

  //update sub category
  public function updateSubCategory(Request $request)
  {
    $request->validate([
      'id' => 'required',

    ]);
    $subcategory_check = DB::table('sub_categories')->where('id', $request->id)->first();
    // dd($subcategory_check);
    if ($request->file('image') !== null) {
      if (File::exists($subcategory_check->image)) {
        File::delete($subcategory_check->image);
      }
      $image = $this->imageUpload($request);
    } else {
      $image = DB::table('sub_categories')->where('id', $request->id)->value('image');
    }
    try {
      DB::table('sub_categories')->where('id', $request->id)
        ->update([
          'name' => $request->name,
          'image' => $image,
          'description' => $request->description,
        ]);
      Toastr::success('sub Category updated');
      return redirect()->route('admin.subcategory');
    } catch (\Exception $e) {
      session()->flash('error-message', $e->getMessage());
      return redirect()->back();
    }
  }
  //delete sub category
  public function deletesubCategory(Request $request)
  {
    try {
      $subcategory_check = DB::table('sub_categories')->where('id', $request->id)->first();
      if (File::exists($subcategory_check->image)) {
        File::delete($subcategory_check->image);
      }
      DB::table('sub_categories')->where('id', $request->id)
        ->delete();
      Toastr::success('Subcategory Deleted');
      return redirect()->route('admin.subcategory');
    } catch (\Exception $e) {
      session()->flash('error-message', $e->getMessage());
      return redirect()->back();
    }
  }
}
