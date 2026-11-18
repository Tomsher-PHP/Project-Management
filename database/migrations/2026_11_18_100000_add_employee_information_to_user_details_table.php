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
        // 1. Add new employee information columns
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

        // 2. Migrate data from old columns and delete data from old columns
        if (Schema::hasColumn('user_details', 'contact_person') && Schema::hasColumn('user_details', 'contact_person_number')) {
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
                    $name = trim($row->contact_person ?? '');
                    $number = trim($row->contact_person_number ?? '');

                    $updateData = [
                        'contact_person' => null,
                        'contact_person_number' => null,
                    ];

                    // Only populate emergency_contacts if not already populated
                    if (empty($row->emergency_contacts) || $row->emergency_contacts === '[]' || $row->emergency_contacts === 'null') {
                        if ($name !== '' || $number !== '') {
                            $contacts = [
                                [
                                    'name' => $name !== '' ? $name : null,
                                    'number' => $number !== '' ? $number : null,
                                    'relation' => null,
                                ],
                            ];
                            $updateData['emergency_contacts'] = json_encode($contacts);
                        }
                    }

                    DB::table('user_details')
                        ->where('id', $row->id)
                        ->update($updateData);
                });

            // 3. Drop the old columns after data migration
            Schema::table('user_details', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('user_details', 'contact_person')) {
                    $columnsToDrop[] = 'contact_person';
                }
                if (Schema::hasColumn('user_details', 'contact_person_number')) {
                    $columnsToDrop[] = 'contact_person_number';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate old columns
        Schema::table('user_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_details', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('user_details', 'contact_person_number')) {
                $table->string('contact_person_number')->nullable()->after('contact_person');
            }
        });

        // 2. Restore primary emergency contact data back to old columns
        if (Schema::hasColumn('user_details', 'emergency_contacts')) {
            DB::table('user_details')
                ->whereNotNull('emergency_contacts')
                ->orderBy('id')
                ->each(function ($row) {
                    $contacts = json_decode($row->emergency_contacts, true);
                    if (is_array($contacts) && !empty($contacts)) {
                        DB::table('user_details')
                            ->where('id', $row->id)
                            ->update([
                                'contact_person' => $contacts[0]['name'] ?? null,
                                'contact_person_number' => $contacts[0]['number'] ?? null,
                            ]);
                    }
                });
        }

        // 3. Drop new columns
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
