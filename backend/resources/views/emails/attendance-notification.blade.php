<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GAFS A-Watch Attendance</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>GAFS A-Watch Attendance Notification</h2>

    <p>Dear Parent/Guardian,</p>

    <p>
        This is to inform you that
        <strong>{{ $student->user->name ?? 'your child' }}</strong>
        (Student No. {{ $student->student_number }})
        has been marked as
        <strong>{{ strtoupper($status) }}</strong>
        for the following class session:
    </p>

    <ul>
        <li><strong>Subject:</strong> {{ $session->subject->name ?? 'N/A' }}</li>
        <li><strong>Section:</strong> {{ $session->section->name ?? 'N/A' }}</li>
        <li><strong>Date:</strong> {{ $session->session_date->format('F j, Y') }}</li>
        <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}</li>
    </ul>

    <p>
        For more details, please visit
        <a href="{{ config('app.frontend_url') }}">{{ config('app.frontend_url') }}</a>.
    </p>

    <p>Thank you,<br>Gamu Agri-Fishery School — A-Watch System</p>
</body>
</html>
