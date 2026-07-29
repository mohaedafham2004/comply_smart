@component('mail::message')
# {{ $heading }}

@foreach ($bodyLines as $line)
{{ $line }}

@endforeach

@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
{{ $actionText }}
@endcomponent

---

*This reminder was generated automatically by **{{ $appName }}**.*
*To manage your reminders and compliance tasks, log in to your dashboard.*

Thanks,<br>
The {{ $appName }} Team
@endcomponent
