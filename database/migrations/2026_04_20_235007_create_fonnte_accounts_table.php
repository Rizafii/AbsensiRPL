<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fonnte_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->unique();
            $table->string('account_name')->nullable();
            $table->string('base_url')->default('https://api.fonnte.com');
            $table->text('token')->nullable();
            $table->string('parent_group_target')->nullable();
            $table->unsignedInteger('timeout')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fonnte_accounts');
    }
};
