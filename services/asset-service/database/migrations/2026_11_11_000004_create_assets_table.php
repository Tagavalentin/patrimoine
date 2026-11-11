<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('designation');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('acquisition_date');
            $table->decimal('acquisition_value', 15, 2);
            $table->unsignedSmallInteger('useful_life_years');
            $table->string('status', 30)->default('acquired')->index();
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index('location_id');
            $table->index('department_id');
            $table->index('acquisition_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
