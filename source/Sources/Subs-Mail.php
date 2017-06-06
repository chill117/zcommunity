<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	//define the receiver of the email
	$to = 'youraddress@example.com';
	//define the subject of the email
	$subject = 'Test email';
	//define the message to be sent. Each line should be separated with \n
	$message = "Hello World!\n\nThis is my first mail.";
	//define the headers we want passed. Note that they are separated with \r\n
	$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";
	//send the email
	$mail_sent = @mail( $to, $subject, $message, $headers );
	
	http://www.webcheatsheet.com/PHP/send_email_text_html_attachment.php
	
	
	for Simple Mail Transfer Protocal (SMTP):
	http://tools.ietf.org/html/rfc5321
	
	zc_send_mail(array $to, string $subject, string $body, array $from = null, bool $send_html = false, int $priority = 5)
		- sends an email to the recipients in the $to array
		- if $priority is 0, the email will be sent immediately...
		- NOTE: $priority will only matter when there is a mail-queue
*/

function zc_send_mail($to, $subject, $body, $from = null, $send_html = false, $priority = 5)
{
	global $zc;
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		require_once($zc['with_software']['sources_dir'] . '/Subs-Post.php');
		sendmail($to, $subject, $body, $from, null, $send_html, $priority);
	}
	// zCommunity's own mailing system...
	else
	{
	}
}

// system for customizing email templates?

?>