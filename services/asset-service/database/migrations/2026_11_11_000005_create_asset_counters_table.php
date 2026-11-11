<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteur annuel pour la génération des codes biens (AST-AAAA-NNNN).
 * Verrouillé (lockForUpdate) dans une transaction pour éviter les collisions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_counters');
    }
};
