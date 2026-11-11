<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_campaign_id')
                ->constrained()
                ->cascadeOnDelete();
            // Instantané du bien au moment du chargement de la campagne
            $table->unsignedBigInteger('asset_id')->index();
            $table->string('asset_code', 30);
            $table->string('asset_designation');
            $table->string('asset_status', 30)->nullable();
            $table->string('department_name')->nullable();
            $table->string('location_name')->nullable();
            // Résultat du contrôle physique
            $table->string('check_status', 20)->default('pending')->index();
            // pending -> found | missing | damaged
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->string('checked_by_name')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_campaign_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lines');
    }
};
