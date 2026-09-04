<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\ParentModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class TestDataSeeder extends Seeder
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Section> */
    private array $sections = [];

    /** @var array<string, Subject> */
    private array $subjects = [];

    /** @var array<string, Teacher> */
    private array $teachers = [];

    /** @var array<string, Student> */
    private array $students = [];

    /** @var array<string, TeachingAssignment> */
    private array $teachingAssignments = [];

    /** @var array<string, ClassSession> */
    private array $classSessions = [];

    public function run(): void
    {
        $path = database_path('seeders/sample-data.json');

        if (! is_file($path)) {
            throw new InvalidArgumentException("Test data file not found: {$path}");
        }

        $this->data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $password = $this->data['meta']['default_password'] ?? 'password';

        $this->seedSections();
        $this->seedSubjects();
        $this->seedUsers($password);
        $this->seedTeachers();
        $this->seedStudents();
        $this->seedParents();
        $this->seedTeachingAssignments();
        $this->seedEnrollments();
        $this->seedClassSessions();
        $this->seedAttendanceRecords();

        $this->command?->info('GAFS test data seeded from database/seeders/sample-data.json');
        $this->command?->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@gafs.edu.ph', $password],
                ['Teacher', 'teacher@gafs.edu.ph', $password],
                ['Student', 'student@gafs.edu.ph', $password],
                ['Parent', 'parent@gafs.edu.ph', $password],
            ],
        );
    }

    private function seedSections(): void
    {
        foreach ($this->data['sections'] as $row) {
            $this->sections[$row['key']] = Section::create([
                'name' => $row['name'],
                'grade_level' => $row['grade_level'],
            ]);
        }
    }

    private function seedSubjects(): void
    {
        foreach ($this->data['subjects'] as $row) {
            $this->subjects[$row['key']] = Subject::create([
                'name' => $row['name'],
            ]);
        }
    }

    private function seedUsers(string $password): void
    {
        foreach ($this->data['users'] as $row) {
            $this->users[$row['key']] = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make($password),
                'role' => $row['role'],
            ]);
        }
    }

    private function seedTeachers(): void
    {
        foreach ($this->data['teachers'] as $row) {
            $teacher = Teacher::create([
                'user_id' => $this->users[$row['user_key']]->id,
            ]);

            $sectionIds = collect($row['section_keys'])
                ->map(fn (string $key) => $this->sections[$key]->id)
                ->all();

            $teacher->sections()->sync($sectionIds);
            $this->teachers[$row['key']] = $teacher;
        }
    }

    private function seedStudents(): void
    {
        foreach ($this->data['students'] as $row) {
            $this->students[$row['key']] = Student::create([
                'user_id' => $this->users[$row['user_key']]->id,
                'student_number' => $row['student_number'],
                'grade_level' => $row['grade_level'],
                'section_id' => $this->sections[$row['section_key']]->id,
                'student_type' => $row['student_type'],
                'parent_email' => $row['parent_email'],
                'qr_token' => $row['qr_token'],
            ]);
        }
    }

    private function seedParents(): void
    {
        foreach ($this->data['parents'] as $row) {
            $parent = ParentModel::create([
                'user_id' => $this->users[$row['user_key']]->id,
                'email' => $row['email'],
            ]);

            $studentIds = collect($row['student_keys'])
                ->map(fn (string $key) => $this->students[$key]->id)
                ->all();

            $parent->students()->sync($studentIds);
        }
    }

    private function seedTeachingAssignments(): void
    {
        foreach ($this->data['teaching_assignments'] as $row) {
            $this->teachingAssignments[$row['key']] = TeachingAssignment::create([
                'teacher_id' => $this->teachers[$row['teacher_key']]->id,
                'subject_id' => $this->subjects[$row['subject_key']]->id,
                'section_id' => $this->sections[$row['section_key']]->id,
            ]);
        }
    }

    private function seedEnrollments(): void
    {
        if ($this->data['enrollment_rules']['auto_enroll_regular_by_section'] ?? false) {
            foreach ($this->students as $student) {
                if ($student->student_type !== 'regular') {
                    continue;
                }

                $assignments = TeachingAssignment::query()
                    ->where('section_id', $student->section_id)
                    ->get();

                foreach ($assignments as $assignment) {
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'teaching_assignment_id' => $assignment->id,
                        'enrollment_type' => 'regular',
                    ]);
                }
            }
        }

        foreach ($this->data['student_enrollments'] ?? [] as $row) {
            StudentEnrollment::create([
                'student_id' => $this->students[$row['student_key']]->id,
                'teaching_assignment_id' => $this->teachingAssignments[$row['teaching_assignment_key']]->id,
                'enrollment_type' => $row['enrollment_type'],
            ]);
        }
    }

    private function seedClassSessions(): void
    {
        foreach ($this->data['class_sessions'] as $row) {
            $session = ClassSession::create([
                'teacher_id' => $this->users[$row['teacher_user_key']]->id,
                'teaching_assignment_id' => $this->teachingAssignments[$row['teaching_assignment_key']]->id,
                'subject_id' => $this->subjects[$row['subject_key']]->id,
                'section_id' => $this->sections[$row['section_key']]->id,
                'session_date' => $this->resolveRelative($row['session_date'])->toDateString(),
                'start_time' => $this->resolveTime($row['start_time']),
                'end_time' => $this->resolveTime($row['end_time']),
                'session_qr_token' => $row['session_qr_token'],
                'expires_at' => $this->resolveRelative($row['expires_at']),
                'absent_processed' => $row['absent_processed'],
                'closed_at' => isset($row['closed_at']) && $row['closed_at']
                    ? $this->resolveRelative($row['closed_at'])
                    : null,
            ]);

            $this->classSessions[$row['key']] = $session;
        }
    }

    private function seedAttendanceRecords(): void
    {
        foreach ($this->data['attendance_records'] ?? [] as $row) {
            AttendanceRecord::create([
                'student_id' => $this->students[$row['student_key']]->id,
                'class_session_id' => $this->classSessions[$row['session_key']]->id,
                'status' => $row['status'],
                'scanned_at' => isset($row['scanned_at']) && $row['scanned_at']
                    ? $this->resolveRelative($row['scanned_at'])
                    : null,
            ]);
        }
    }

    private function resolveRelative(string $value): Carbon
    {
        return match ($value) {
            'relative:today' => now()->startOfDay(),
            'relative:yesterday' => now()->subDay()->startOfDay(),
            'relative:yesterday_end' => now()->subDay()->setTime(23, 59, 59),
            'relative:minus_30_min' => now()->subMinutes(30),
            'relative:minus_1_hour' => now()->subHour(),
            'relative:minus_2_hours' => now()->subHours(2),
            'relative:minus_90_min' => now()->subMinutes(90),
            'relative:plus_1_hour' => now()->addHour(),
            'relative:plus_2_hours' => now()->addHours(2),
            'relative:yesterday_morning' => now()->subDay()->setTime(8, 15, 0),
            default => Carbon::parse($value),
        };
    }

    private function resolveTime(string $value): string
    {
        if (str_starts_with($value, 'relative:')) {
            return $this->resolveRelative($value)->format('H:i:s');
        }

        return strlen($value) === 5 ? "{$value}:00" : $value;
    }
}
