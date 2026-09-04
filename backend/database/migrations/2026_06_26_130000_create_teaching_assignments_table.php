<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id', 'section_id']);
        });

        $subjects = DB::table('subjects')->whereNotNull('teacher_id')->get();

        foreach ($subjects as $subject) {
            $teacher = DB::table('teachers')->where('user_id', $subject->teacher_id)->first();

            if (! $teacher) {
                continue;
            }

            $sectionIds = DB::table('teacher_section')
                ->where('teacher_id', $teacher->id)
                ->pluck('section_id');

            foreach ($sectionIds as $sectionId) {
                DB::table('teaching_assignments')->insertOrIgnore([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'section_id' => $sectionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('name')->constrained('users')->nullOnDelete();
        });

        Schema::dropIfExists('teaching_assignments');
    }
};
