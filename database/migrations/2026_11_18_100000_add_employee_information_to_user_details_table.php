<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_details', 'emergency_contacts')) {
                $table->json('emergency_contacts')->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('user_details', 'native_address')) {
                $table->mediumText('native_address')->nullable()->after('address');
            }
            if (!Schema::hasColumn('user_details', 'native_contacts')) {
                $table->json('native_contacts')->nullable()->after('emergency_contacts');
            }
            if (!Schema::hasColumn('user_details', 'marital_status')) {
                $table->string('marital_status', 30)->nullable()->after('native_contacts');
            }
            if (!Schema::hasColumn('user_details', 'children_count')) {
                $table->unsignedTinyInteger('children_count')->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('user_details', 'favorite_color')) {
                $table->string('favorite_color', 30)->nullable()->after('children_count');
            }
            if (!Schema::hasColumn('user_details', 'shirt_sizes')) {
                $table->json('shirt_sizes')->nullable()->after('favorite_color');
            }
            if (!Schema::hasColumn('user_details', 'shoe_sizes')) {
                $table->json('shoe_sizes')->nullable()->after('shirt_sizes');
            }
        });

        // Safely and idempotently migrate existing contact_person and contact_person_number
        DB::table('user_details')
            ->where(function ($query) {
                $query->whereNotNull('contact_person')
                    ->where('contact_person', '!=', '')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('contact_person_number')
                            ->where('contact_person_number', '!=', '');
                    });
            })
            ->orderBy('id')
            ->each(function ($row) {
                // Skip if emergency_contacts is already populated
                if (!empty($row->emergency_contacts) && $row->emergency_contacts !== '[]' && $row->emergency_contacts !== 'null') {
                    return;
                }

                $name = trim($row->contact_person ?? '');
                $number = trim($row->contact_person_number ?? '');

                if ($name !== '' || $number !== '') {
                    $contacts = [
                        [
                            'name' => $name !== '' ? $name : null,
                            'number' => $number !== '' ? $number : null,
                            'relation' => null,
                        ],
                    ];

                    DB::table('user_details')
                        ->where('id', $row->id)
                        ->update([
                            'emergency_contacts' => json_encode($contacts),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach ([
                'emergency_contacts',
                'native_address',
                'native_contacts',
                'marital_status',
                'children_count',
                'favorite_color',
                'shirt_sizes',
                'shoe_sizes',
            ] as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

