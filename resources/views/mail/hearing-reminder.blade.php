Hearing reminder

Case: {{ $hearing->legalCase->case_number }} - {{ $hearing->legalCase->title }}
Date: {{ $hearing->hearing_date->format('d M Y H:i') }}
Court room: {{ $hearing->court_room ?: 'Not specified' }}
Purpose: {{ $hearing->purpose ?: 'Not specified' }}

Add to Google Calendar:
{{ $hearing->googleCalendarUrl() }}
