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
        Schema::create('elements', function (Blueprint $table) {
            $table->id();
						$table->string('generated_id');
						$table->foreignId('unit_id')->constrained()->onDelete('cascade');
						$table->string('filepath')->nullable();
						$table->string('name');
						$table->string('filename');
						$table->integer('processed')->default(0);
						$table->string('type');
						$table->integer('duration');
						$table->integer('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elements');
    }
};
