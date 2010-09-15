BEGIN:VCALENDAR
PRODID:-//C.S.R. Delft/Webstek C.S.R. Delft//NL
VERSION:2.0

BEGIN:VTIMEZONE
TZID:Europe/Amsterdam
X-LIC-LOCATION:Europe/Amsterdam
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE
BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE

{foreach from=$items item=item}
{if $item instanceof Lid}{* 
	geen verjaardagen hier. 
*}{else}
BEGIN:VEVENT
SUMMARY:{$item->getTitel()}
{if 
$item->isHeledag()}DTSTART;VALUE=DATE:{$item->getBeginMoment()|date_format:'%Y%m%d'}{else}DTSTART;TZID=Europe/Amsterdam:{$item->getBeginMoment()|date_format:'%Y%m%dT%H%M%S'}{/if}

{if 
$item->isHeledag()}DTEND;VALUE=DATE:{$item->getEindMoment()|date_format:'%Y%m%d'}{else}DTEND;TZID=Europe/Amsterdam:{$item->getEindMoment()|date_format:'%Y%m%dT%H%M%S'}{/if}

{*
X-GOOGLE-CALENDAR-CONTENT-TITLE:{$item->getTitel()}
{if $item instanceof Maaltijd}
X-GOOGLE-CALENDAR-CONTENT-ICON:http://plaetjes.csrdelft.nl/famfamfam/cup.png
{else}
X-GOOGLE-CALENDAR-CONTENT-ICON:http://plaetjes.csrdelft.nl/layout/favicon.ico
{/if}
*}
END:VEVENT
{/if}
{/foreach}
END:VCALENDAR
