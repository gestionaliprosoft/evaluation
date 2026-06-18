<?php

namespace App\Enums;

enum AutomationEnum: string
{
    public const CREATE_TICKET = 'createTicket';

    public const SEND_EMAIL = 'sendEmail';

    public const SEND_NOTIFICATION = 'sendNotification';

    public const CREATE_CALENDAR_REMINDER = 'createCalendarReminder';

    public const EXECUTE_FUNCION = 'executeFunction';
}
