<?php

namespace Database\Seeders;

use App\Models\CustomerProfileGrade;
use Illuminate\Database\Seeder;

class CustomerProfileGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['name' => 'Platinum', 'code' => 'platinum', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="platinum-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse"><stop stop-color="#C4A7FF"/><stop offset=".5" stop-color="#7C3AED"/><stop offset="1" stop-color="#4C1D95"/></linearGradient></defs><path fill="url(#platinum-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z"/><path fill="#fff" fill-opacity=".72" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z"/></svg>', 'color' => '#6D28D9', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Gold', 'code' => 'gold', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="gold-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse"><stop stop-color="#FFF3A3"/><stop offset=".48" stop-color="#F5B800"/><stop offset="1" stop-color="#A96800"/></linearGradient></defs><path fill="url(#gold-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z"/><path fill="#fff" fill-opacity=".55" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z"/></svg>', 'color' => '#A16207', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Silver', 'code' => 'silver', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="silver-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse"><stop stop-color="#F8FAFC"/><stop offset=".38" stop-color="#CBD5E1"/><stop offset=".68" stop-color="#94A3B8"/><stop offset="1" stop-color="#475569"/></linearGradient></defs><path fill="url(#silver-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z"/><path fill="#fff" fill-opacity=".65" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z"/></svg>', 'color' => '#475569', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'Bronze', 'code' => 'bronze', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="bronze-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse"><stop stop-color="#F4C28B"/><stop offset=".45" stop-color="#C7783A"/><stop offset="1" stop-color="#713617"/></linearGradient></defs><path fill="url(#bronze-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z"/><path fill="#FFE5CC" fill-opacity=".58" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z"/></svg>', 'color' => '#9A4D1F', 'sort_order' => 4, 'is_active' => true],
            ['name' => 'New', 'code' => 'new', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="new-sprout" x1="5" y1="4" x2="15" y2="17" gradientUnits="userSpaceOnUse"><stop stop-color="#86EFAC"/><stop offset=".55" stop-color="#22C55E"/><stop offset="1" stop-color="#15803D"/></linearGradient></defs><path fill="url(#new-sprout)" d="M10.8 9.35c.42-3.63 2.62-5.67 6.57-6.1.16 3.97-1.78 6.24-5.82 6.8A8.87 8.87 0 0 0 10.7 12v4.25a.75.75 0 0 1-1.5 0v-3.47a8.67 8.67 0 0 0-1.05-2.39C4.53 10.06 2.66 8.1 2.55 4.5c3.77.18 5.94 1.8 6.5 4.86.45.5.82 1.03 1.12 1.56.18-.52.39-1.05.63-1.57Z"/><path fill="#DCFCE7" fill-opacity=".7" d="M12.3 8.16c.83-1.43 2.02-2.48 3.58-3.14-1.02 1.42-2.21 2.47-3.58 3.14ZM4.35 6.22c1.43.57 2.54 1.45 3.34 2.64-1.37-.48-2.48-1.36-3.34-2.64Z"/></svg>', 'color' => '#15803D', 'sort_order' => 5, 'is_active' => true],
        ];

        foreach ($grades as $grade) {
            CustomerProfileGrade::firstOrCreate(
                ['code' => $grade['code']],
                $grade,
            );
        }
    }
}
