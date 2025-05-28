Hi there!

We're thrilled to receive your interest in joining the {{ config('app.name') }} Early Access Program! I wanted to personally thank you for taking the time to apply.

I see you're from {{ $application->organization_name }} - it's great to have you here! We're always excited to welcome {{ strtolower($application->organization_type) }}s like yours to our community.

I've noted down what you shared about your organization:
{{ $application->website ? "Website: " . $application->website : "" }}

Your description really helps us understand your needs:
{{ $application->description }}

Our team is reviewing your application with great interest, and we'll get back to you very soon. If you have any questions or just want to chat about how {{ config('app.name') }} could help your organization, feel free to reply to this email directly - I'm here to help!

You can also check out our website at {{ config('app.url') }} for more information while you wait.

Looking forward to potentially working together!

Best wishes,
The {{ config('app.name') }} Team

P.S. We're really excited about the possibility of having you on board! 