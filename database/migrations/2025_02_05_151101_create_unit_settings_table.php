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
        Schema::create('unit_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('unit_id')->constrained()->onDelete('cascade'); // Link to Unit
        $table->string('key'); // Setting name
        $table->string('value'); // Setting value (use text if needed)
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_settings');
    }
};
