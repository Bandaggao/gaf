<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GAFS A-Watch — Student Arrived</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>GAFS A-Watch — Student Arrival Notification</h2>

    <p>Dear Parent/Guardian,</p>

    <p>
        This is to inform you that
        <strong>{{ $student->user->name ?? 'your child' }}</strong>
        (Student No. {{ $student->student_number }})
        has arrived at the school premises.
    </p>

    <ul>
        <li><strong>Date:</strong> {{ $entry->scan_date->format('F j, Y') }}</li>
        <li><strong>Time:</strong> {{ $entry->scanned_at->copy()->timezone('Asia/Manila')->format('h:i A') }}</li>
    </ul>

    <p>
        For more details, please visit
        <a href="{{ config('app.frontend_url') }}">{{ config('app.frontend_url') }}</a>.
    </p>

    <p>Thank you,<br>Gamu Agri-Fishery School — A-Watch System</p>
</body>
</html>
