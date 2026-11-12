<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 50); // dashboard, assets_csv, maintenance_csv…
            $table->string('format', 10)->default('json');
            $table->json('filters')->nullable();
            $table->unsignedInteger('rows_count')->default(0);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->string('generated_by_name')->nullable();
            $table->string('generated_by_email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['report_type', 'created_at']);
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
    }
};
