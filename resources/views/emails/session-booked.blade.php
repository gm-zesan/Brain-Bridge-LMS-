<!DOCTYPE html>
<html>
<head>
    <title>Lesson Session Scheduled</title>
</head>
<body>
    <h2>Lesson Session Scheduled</h2>
    <p>Hello {{ $role == 'teacher' ? $session->teacher->name : $session->student->name }},</p>

    <p>Your lesson session has been scheduled:</p>
    <ul>
        <li>Subject: {{ $session->subject->name }}</li>
        <li>Teacher: {{ $session->teacher->name }}</li>
        <li>Student: {{ $session->student->name }}</li>
        <li>Date: {{ $session->scheduled_start_time->format('d M Y') }}</li>
        <li>Time: {{ $session->scheduled_start_time->format('H:i') }} - {{ $session->scheduled_end_time->format('H:i') }}</li>
        <li>Meeting Link: <a href="{{ $session->meeting_link }}">{{ $session->meeting_link }}</a></li>
    </ul>

    <p>Thanks,<br>BrainBridge Team</p>
</body>
</html>
