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
        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('title', 'title_ar');
            $table->renameColumn('description', 'description_ar');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title_ar');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->enum('translation_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('description_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en', 'translation_status']);
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('title_ar', 'title');
            $table->renameColumn('description_ar', 'description');
        });
    }
};
