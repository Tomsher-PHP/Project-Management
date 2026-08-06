<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appraisal_categories', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->after('id');
        });

        $categories = DB::table('appraisal_categories')->get();
        $usedCodes = [];

        foreach ($categories as $category) {
            do {
                $code = 'APC-' . strtoupper(Str::random(8));
            } while (in_array($code, $usedCodes, true));

            $usedCodes[] = $code;

            DB::table('appraisal_categories')
                ->where('id', $category->id)
                ->update(['code' => $code]);
        }

        Schema::table('appraisal_categories', function (Blueprint $table) {
            $table->string('code', 30)->nullable(false)->change();
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
