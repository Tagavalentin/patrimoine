<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique(); // INV-AAAA-NNNN
            $table->string('name');
            // Périmètre optionnel : ids des référentiels asset-service
            // (pas de FK inter-bases, on stocke l'id + le libellé).
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('department_name')->nullable();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('location_name')->nullable();
            $table->string('status', 20)->default('draft')->index();
            // draft -> in_progress -> completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->string('created_by_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_campaigns');
    }
};
