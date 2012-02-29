<?php
global $base;
$base="../..";
$base=$CFG->dirroot;
require_once($base."/config.php");



function reminder_cron()
{
mtrace("\n========== Checking reminders START ===========");
sendReminders();
mtrace("========== Checking reminders END ===========");
}

function sendReminders()
{
$prev=48*3600;

$cond= '((eventtype="close" AND modulename="feedback") OR (eventtype="due" AND modulename="assignment")) ';
$now = time();

$lastReminder=get_config(NULL, 'REMINDER_LAST_MESSAGES_TIMESTAMP'); 

if (!$lastReminder)
 $lastReminder=$now;

$next_events_timestamp=$now+$prev;
$select="$cond" . "AND (timestart<=$next_events_timestamp AND timestart>$lastReminder)";

mtrace("Searching events from ".date(DATE_RFC822,$lastReminder)." to ".date(DATE_RFC822,$next_events_timestamp));
mtrace("query:$select");

$events = get_records_select('event',$select);

if ($events==false)
{
	mtrace('No reminders');
}
else
{
	mtrace('Sending '.count($events).' events.');
	foreach ($events as $event)
	{
		send_event($event);
	}	
	set_config('REMINDER_LAST_MESSAGES_TIMESTAMP', $next_events_timestamp);
}


}
/**
 * Send a message to users depending on the type of event
 * @param unknown_type $event
 */
function send_event($event)
{
global $CFG, $base;

if (!empty($CFG->messaging)) 
{
require_once("$base/message/lib.php");
}
else
{
	mtrace("WARNING! no messaging system! Use direct email!");
}
   /// General message information.
        $userfrom = get_admin();
        $site     = get_site();
        $subject  = "Aviso de próxima fecha límite de evento importante en: $site->fullname";
        $message  = reminder_message($event);

       
        /// Get all the users in the course and send them the reminder
        $users = get_course_users($event->courseid);


        foreach ($users as $user) {
        	if (!empty($CFG->messaging))
        	{
        		print_object($message);
        		message_post_message($userfrom, $user, $message, 0, 'direct');
        	} else
        	{
        		print_object($message);
        		email_to_user($user, $userfrom, $subject, $message);
        	}

        	mtrace('Sent reminder "' . $event->name . '" to user ' . fullname($user) . "\n");
        }
}
/**
 * Returns a formatted upcoming event reminder message for a course.
 *
 * @param int $eventid The ID of the event to format a message for.
 */
    function reminder_message($event)
    {
     /// Get the course record to format message variables
     $course = get_record('course', 'id', $event->courseid);
     $message = "<p>Hay una fecha límite importante próxima a cumplirse en \"$course->fullname\": </p>";

    /// Add the date for the event and the description for the event to the end of the message.
     $message .= "<p>La fecha límite es: ". userdate($event->timestart)." Por favor verifique que ha completado sus tareas.</p>";
     $message .= format_text($event->description);
     return $message;
    }
?>
