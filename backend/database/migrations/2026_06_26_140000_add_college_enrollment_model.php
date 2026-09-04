<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_type')->default('regular')->after('section_id');
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('enrollment_type', ['regular', 'irregular'])->default('regular');
            $table->timestamps();

            $table->unique(['student_id', 'teaching_assignment_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('teaching_assignment_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained()
                ->nullOnDelete();
        });

        $students = DB::table('students')->get();

        foreach ($students as $student) {
            $assignments = DB::table('teaching_assignments')
                ->where('section_id', $student->section_id)
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('student_enrollments')->insertOrIgnore([
                    'student_id' => $student->id,
                    'teaching_assignment_id' => $assignment->id,
                    'enrollment_type' => 'regular',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $sessions = DB::table('class_sessions')->get();

        foreach ($sessions as $session) {
            $teacher = DB::table('teachers')->where('user_id', $session->teacher_id)->first();

            if (! $teacher) {
                continue;
            }

            $assignment = DB::table('teaching_assignments')
                ->where('teacher_id', $teacher->id)
                ->where('subject_id', $session->subject_id)
                ->where('section_id', $session->section_id)
                ->first();

            if ($assignment) {
                DB::table('class_sessions')
                    ->where('id', $session->id)
                    ->update(['teaching_assignment_id' => $assignment->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['teaching_assignment_id']);
            $table->dropColumn('teaching_assignment_id');
        });

        Schema::dropIfExists('student_enrollments');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('student_type');
        });
    }
};
