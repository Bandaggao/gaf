<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily rotating QR token on students
        Schema::table('students', function (Blueprint $table) {
            $table->string('daily_qr_token')->nullable()->unique()->after('qr_token');
            $table->date('daily_qr_date')->nullable()->after('daily_qr_token');
        });

        // Gate entries — one per student per day
        Schema::create('gate_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('scan_date');
            $table->timestamp('scanned_at');
            $table->string('qr_token_used')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'scan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_entries');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['daily_qr_token', 'daily_qr_date']);
        });
    }
};
