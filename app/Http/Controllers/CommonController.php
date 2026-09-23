<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountryTimezones;
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

    public function searchTimezones(Request $request)
    {
        $query = $request->input('q', '');

        $timezones = CountryTimezones::select('zone_name')
            ->when($query, function ($q) use ($query) {
                $q->where('zone_name', 'like', "%{$query}%");
            })
            ->distinct()
            ->orderBy('zone_name')
            ->limit(30)
            ->pluck('zone_name')
            ->toArray();

        if (empty($query) || str_contains(strtolower('UTC'), strtolower($query))) {
            if (!in_array('UTC', $timezones)) {
                array_unshift($timezones, 'UTC');
            }
        }

        $results = array_map(function ($tz) {
            return [
                'id' => $tz,
                'name' => $tz,
            ];
        }, $timezones);

        return response()->json($results);
    }

    public function searchCurrencies(Request $request)
    {
        $query = $request->input('q', '');

        $currencies = Country::select('currency', 'currency_symbol')
            ->whereNotNull('currency')
            ->where('currency', '!=', '')
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('currency', 'like', "%{$query}%")
                        ->orWhere('currency_symbol', 'like', "%{$query}%");
                });
            })
            ->groupBy('currency', 'currency_symbol')
            ->orderBy('currency')
            ->limit(30)
            ->get();

        $results = $currencies->map(function ($item) {
            $label = $item->currency_symbol
                ? "{$item->currency} ({$item->currency_symbol})"
                : $item->currency;

            return [
                'id' => $item->currency,
                'name' => $label,
            ];
        });

        return response()->json($results);
    }
}
