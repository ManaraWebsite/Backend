<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->renameColumn('label', 'label_ar');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('label_en')->nullable()->after('label_ar');
            // Parallel, index-aligned translations of `options` (the Arabic values
            // in `options` remain the canonical values used for validation and
            // stored submission answers — see SubmitFormRequest).
            $table->json('options_en')->nullable()->after('options');
            $table->enum('translation_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('options_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'options_en', 'translation_status']);
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->renameColumn('label_ar', 'label');
        });
    }
};
