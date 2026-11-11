<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('technician_name')->nullable();
            $table->text('description');
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('performed_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('maintenance_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
