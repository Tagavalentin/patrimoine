<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->string('reference', 30)->unique(); // DSP-AAAA-NNNN
            $table->string('disposal_type', 30); // sale, donation, destruction, written_off
            $table->text('reason');
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->string('status', 20)->default('requested')->index();
            // requested -> approved -> completed | rejected (depuis requested)
            $table->date('disposal_date')->nullable();
            $table->decimal('sale_value', 14, 2)->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('requested_by_name')->nullable();
            $table->string('requested_by_email')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposals');
    }
};
