<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .enrollment-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .detail-label { font-weight: bold; color: #6b7280; }
        .detail-value { color: #111827; }
        .button { display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .info-badge { background: #3b82f6; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 New Student Enrollment</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $enrollment->teacher->name }},</p>
            
            <p>Great news! A new student has enrolled in your course.</p>
            
            <div class="enrollment-details">
                <h2 style="margin-top: 0; color: #111827;">Enrollment Information</h2>
                
                <div class="detail-row">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value">{{ $enrollment->student->name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Student Email:</span>
                    <span class="detail-value">{{ $enrollment->student->email }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Course:</span>
                    <span class="detail-value">{{ $enrollment->course->title }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">{{ $enrollment->course->subject->name ?? 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Enrolled On:</span>
                    <span class="detail-value">{{ $enrollment->enrolled_at->format('F d, Y \a\t h:i A') }}</span>
                </div>
                
                @if($enrollment->isPaid())
                <div class="detail-row">
                    <span class="detail-label">Revenue:</span>
                    <span class="detail-value" style="color: #059669; font-weight: bold;">
                        {{ $enrollment->formatted_amount }} {{ strtoupper($enrollment->currency) }}
                    </span>
                </div>
                @else
                <div class="detail-row">
                    <span class="detail-label">Enrollment Type:</span>
                    <span class="detail-value">
                        <span class="info-badge">FREE ENROLLMENT</span>
                    </span>
                </div>
                @endif
            </div>
            
            <p style="margin-top: 30px;">
                <a href="{{ config('app.url') }}/teacher/courses/{{ $enrollment->course_id }}/students" class="button">
                    View All Students
                </a>
            </p>
            
            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                <strong>Keep up the great work!</strong><br>
                Your course is attracting new students. Continue providing quality content and engaging with your learners.
            </p>
        </div>
        
        <div class="footer">
            <p>Thank you for being an amazing instructor!</p>
            <p style="font-size: 12px;">
                Questions? Contact us at 
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
        </div>
    </div>
</body>
</html>