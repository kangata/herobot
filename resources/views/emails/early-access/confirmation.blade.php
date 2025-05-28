@component('mail::message')
# Thank You for Your Interest!

Dear {{ $application->organization_name }},

We have received your early access application for {{ config('app.name') }}. Thank you for your interest in our platform!

Here's a summary of your application:

**Organization Type:** {{ ucfirst($application->organization_type) }}  
**Website:** {{ $application->website ?: 'Not provided' }}

**Your Description:**  
{{ $application->description }}

Our team will review your application and get back to you soon. If you have any questions in the meantime, feel free to reply to this email.

@component('mail::button', ['url' => config('app.url')])
Visit Our Website
@endcomponent

Best regards,  
The {{ config('app.name') }} Team
@endcomponent 