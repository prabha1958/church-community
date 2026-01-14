@component('mail::message')
    # Your One-Time Login Code

    Use the following code to login:

    **{{ $code }}**

    It expires in 10 minutes. If you didn't request this, ignore this email.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
