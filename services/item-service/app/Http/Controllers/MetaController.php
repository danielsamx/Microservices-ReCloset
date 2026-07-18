<?php
namespace App\Http\Controllers;
use App\Models\Category; use App\Models\Size; use App\Models\Color;
class MetaController extends Controller
{
    public function index()
    {
        return response()->json([
            'categories' => Category::orderBy('name')->get(),
            'sizes' => Size::orderBy('position')->get(),
            'colors' => Color::orderBy('name')->get(),
        ]);
    }
}
