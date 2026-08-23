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
        Schema::table('field_voices', function (Blueprint $table) {
            $table->renameColumn('role', 'role_ar');
            $table->renameColumn('quote', 'quote_ar');
        });

        Schema::table('field_voices', function (Blueprint $table) {
            $table->string('role_en')->nullable()->after('role_ar');
            $table->text('quote_en')->nullable()->after('quote_ar');
            $table->enum('translation_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('quote_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_voices', function (Blueprint $table) {
            $table->dropColumn(['role_en', 'quote_en', 'translation_status']);
        });

        Schema::table('field_voices', function (Blueprint $table) {
            $table->renameColumn('role_ar', 'role');
            $table->renameColumn('quote_ar', 'quote');
        });
    }
};
