<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .course-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .detail-label { font-weight: bold; color: #6b7280; }
        .detail-value { color: #111827; }
        .button { display: inline-block; background: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .success-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Course Enrollment Confirmed!</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $enrollment->student->name }},</p>
            
            <p>Congratulations! You have successfully enrolled in the course. You can now start learning right away.</p>
            
            <div class="course-details">
                <h2 style="margin-top: 0; color: #111827;">Course Details</h2>
                
                <div class="detail-row">
                    <span class="detail-label">Course Title:</span>
                    <span class="detail-value">{{ $enrollment->course->title }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">{{ $enrollment->course->subject->name ?? 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Instructor:</span>
                    <span class="detail-value">{{ $enrollment->teacher->name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Enrolled On:</span>
                    <span class="detail-value">{{ $enrollment->enrolled_at->format('F d, Y \a\t h:i A') }}</span>
                </div>
                
                @if($enrollment->isPaid())
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">{{ $enrollment->formatted_amount }} {{ strtoupper($enrollment->currency) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value">
                        <span class="success-badge">PAID</span>
                    </span>
                </div>
                
                @if($enrollment->payment_intent_id)
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value" style="font-size: 12px;">{{ $enrollment->payment_intent_id }}</span>
                </div>
                @endif
                @else
                <div class="detail-row">
                    <span class="detail-label">Enrollment Type:</span>
                    <span class="detail-value">
                        <span class="success-badge">FREE</span>
                    </span>
                </div>
                @endif
            </div>
            
            <p style="margin-top: 30px;">
                <a href="{{ config('app.url') }}/courses/{{ $enrollment->course_id }}" class="button">
                    Start Learning Now
                </a>
            </p>
            
            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                <strong>What's Next?</strong><br>
                • Access all course modules and video lessons<br>
                • Track your progress as you complete each section<br>
                • Reach out to your instructor if you have questions<br>
                • Get a certificate upon completion
            </p>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing our platform!</p>
            <p style="font-size: 12px;">
                If you have any questions, please contact us at 
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
        </div>
    </div>
</body>
</html>