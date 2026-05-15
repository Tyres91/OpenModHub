<?php

use App\Models\SecurityCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default(SecurityCheck::PROVIDER_VIRUSTOTAL)->index();
            $table->string('status')->default(SecurityCheck::STATUS_NOT_SUBMITTED)->index();
            $table->string('external_url', 2048)->nullable();
            $table->string('analysis_id')->nullable()->index();
            $table->text('result_summary')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_checks');
    }
};
