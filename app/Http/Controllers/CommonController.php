<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    // CountryController.php
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $countries = Country::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($countries);
    }
}
