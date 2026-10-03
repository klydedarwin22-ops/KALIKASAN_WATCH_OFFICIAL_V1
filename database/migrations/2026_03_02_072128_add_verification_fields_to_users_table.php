<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('barangay_verified')->default(false)->after('barangay');
            $table->string('barangay_id_path')->nullable()->after('barangay_verified');
            $table->text('verification_notes')->nullable()->after('barangay_id_path');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'barangay_verified',
                'barangay_id_path',
                'verification_notes',
                'verified_at',
                'verified_by',
            ]);
        });
    }
};
