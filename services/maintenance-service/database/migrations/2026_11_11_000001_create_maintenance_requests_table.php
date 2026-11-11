<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique(); // MNT-AAAA-NNNN
            // Le bien vit dans asset_db : pas de FK, on stocke
            // l'id + un instantané code/désignation/statut.
            $table->unsignedBigInteger('asset_id')->index();
            $table->string('asset_code', 30);
            $table->string('asset_designation');
            $table->string('asset_status', 30)->nullable(); // statut au moment de la demande
            $table->string('asset_previous_status', 30)->nullable(); // statut avant intervention
            $table->text('description');
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->string('status', 20)->default('requested')->index();
            // requested -> scheduled -> in_progress -> completed
            $table->date('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();   // sub JWT
            $table->string('requested_by_name')->nullable();
            $table->string('requested_by_email')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();    // technicien (sub JWT)
            $table->string('assigned_to_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
