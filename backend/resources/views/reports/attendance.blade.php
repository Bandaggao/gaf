<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        h1 { font-size: 18px; }
    </style>
</head>
<body>
    <h1>GAFS A-Watch — Attendance Report</h1>
    <p>Generated: {{ now()->format('F j, Y g:i A') }}</p>
    @if($from || $to)
        <p>Period: {{ $from ?? '—' }} to {{ $to ?? '—' }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Scanned At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->classSession->session_date->format('Y-m-d') }}</td>
                    <td>{{ $record->student->user->name ?? 'N/A' }}</td>
                    <td>{{ $record->classSession->section->name ?? 'N/A' }}</td>
                    <td>{{ $record->classSession->subject->name ?? 'N/A' }}</td>
                    <td>{{ strtoupper($record->status) }}</td>
                    <td>{{ $record->scanned_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
