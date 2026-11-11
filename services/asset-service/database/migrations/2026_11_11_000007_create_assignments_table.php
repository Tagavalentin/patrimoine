<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            // L'utilisateur responsable vit dans l'auth-service (pas de FK
            // possible) : on stocke son id + un instantané nom/email.
            $table->unsignedBigInteger('responsible_user_id')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('responsible_email')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // sub du JWT
            $table->timestamps();

            // Recherche de l'affectation active d'un bien
            $table->index(['asset_id', 'end_date']);
            $table->index(['department_id', 'end_date']);
            $table->index('responsible_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
