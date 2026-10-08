<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('citizen_certifications');
    }

    public function down(): void
    {
        // Certification data is intentionally not restored on rollback.
    }
};
