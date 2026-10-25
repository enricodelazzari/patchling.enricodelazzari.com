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
        Schema::create('results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('game_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 20)->nullable();
            $table->string('claim_token', 64);
            $table->unsignedInteger('score')->index();
            $table->string('verdict');
            $table->unsignedTinyInteger('months');
            $table->unsignedInteger('revenue');
            $table->unsignedSmallInteger('features');
            $table->unsignedSmallInteger('squashed');
            $table->unsignedTinyInteger('breaches');
            $table->unsignedTinyInteger('health');
            $table->unsignedTinyInteger('happy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
