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
            ['name' => 'New', 'code' => 'new', 'badge_svg' => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="new-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse"><stop stop-color="#BBF7D0"/><stop offset=".48" stop-color="#22C55E"/><stop offset="1" stop-color="#15803D"/></linearGradient></defs><path fill="url(#new-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z"/><path fill="#ECFDF5" fill-opacity=".68" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z"/></svg>', 'color' => '#15803D', 'sort_order' => 5, 'is_active' => true],
        ];

        foreach ($grades as $grade) {
            CustomerProfileGrade::firstOrCreate(
                ['code' => $grade['code']],
                $grade,
            );
        }
    }
}
