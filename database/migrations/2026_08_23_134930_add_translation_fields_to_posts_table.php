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
        Schema::table('posts', function (Blueprint $table) {
            $table->renameColumn('title', 'title_ar');
            $table->renameColumn('content', 'content_ar');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title_ar');
            $table->text('content_en')->nullable()->after('content_ar');
            $table->enum('translation_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('content_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'content_en', 'translation_status']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->renameColumn('title_ar', 'title');
            $table->renameColumn('content_ar', 'content');
        });
    }
};
