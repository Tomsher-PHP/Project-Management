<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountryTimezones;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // truncate before insert
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Country::truncate();
        CountryTimezones::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = json_decode(file_get_contents(database_path('data/countries.json')), true);
        foreach ($data as $value) {
            $country = Country::create([
                'name' => $value['name'],
                'code' => $value['iso2'],
                'phonecode' => $value['phonecode'],
                'currency' => $value['currency'],
                'currency_symbol' => $value['currency_symbol'],
            ]);

            if (isset($value['timezones']) && !empty($value['timezones'])) {
                foreach ($value['timezones'] as $timezone) {
                    CountryTimezones::create([
                        'country_id' => $country->id,
                        'zone_name' => $timezone['zoneName'],
                        'gmt_offset' => $timezone['gmtOffset'],
                        'gmt_offset_name' => $timezone['gmtOffsetName'],
                        'abbreviation' => $timezone['abbreviation'],
                        'tz_name' => $timezone['tzName'],
                    ]);
                }
            }
        }
    }
}
