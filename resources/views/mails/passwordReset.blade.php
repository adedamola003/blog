<x-mail::message>
    You are getting this mail because you requested for a password reset on your {{ config('app.name') }} account.

    Please enter the OTP below to reset your account password

    {{$token}}

    Please note that this OTP will expire in {{config('settings.passwordResetTokenExpiry')}} minutes.

    Thanks,
    {{ config('app.name') }}
</x-mail::message>
