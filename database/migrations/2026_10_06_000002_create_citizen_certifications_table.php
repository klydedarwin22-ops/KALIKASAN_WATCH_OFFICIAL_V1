<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('contribution_note');
            $table->timestamp('issued_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['citizen_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_certifications');
    }
};
