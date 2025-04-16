<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::select('id', 'name')->get(), 200);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:categories']);
        $category = Category::create(['name' => $request->name]);
        return response()->json($category, 201);
    }
}
