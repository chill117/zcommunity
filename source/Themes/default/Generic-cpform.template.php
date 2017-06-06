<?php

if (!defined('zc'))
	die('Hacking attempt...');

function template_zc_auto_submit_form()
{
	global $txt, $context;
	
	zc_template_sandwich_above();
	
	if (!empty($context['zc']['auto_submit_form_title']))
		echo '
			<div class="needsPadding" style="font-size:13px; text-align:left;"><b>', $context['zc']['auto_submit_form_title'], '</b></div>';
			
	if (!empty($context['zc']['continue_percent']))
		echo '
			<div style="padding-left: 20%; padding-right: 20%; margin-top: 1ex;">
				<div style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; padding: 1px; position: relative;">
					<div style="padding-top: ', $context['browser']['is_safari'] || $context['browser']['is_konqueror'] ? '2pt' : '1pt', '; width: 100%; z-index: 2; color: black; position: absolute; text-align: center; font-weight: bold;">', $context['zc']['continue_percent'], '%</div>
					<div style="width: ', $context['zc']['continue_percent'], '%; height: 12pt; z-index: 1; background-color: red;">&nbsp;</div>
				</div>
			</div>';
			
	echo '
			<form action="', $context['zc']['continue_get_data'], '" method="post" accept-charset="', $context['character_set'], '" style="margin: 0;" name="autoSubmit" id="autoSubmit">
				<div class="needsPadding" style="text-align:right;">
					<input type="submit" name="cont" value="', $txt['b670'], '" />
					', $context['zc']['continue_post_data'], '
				</div>
			</form>';
	
	zc_template_sandwich_below();
	
	echo '
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		var countdown = ', $context['zc']['continue_countdown'], ';
		doAutoSubmit();

		function doAutoSubmit()
		{
			if (countdown == 0)
				document.forms.autoSubmit.submit();
			else if (countdown == -1)
				return;

			document.forms.autoSubmit.cont.value = "', $txt['b669'], ' (" + countdown + ")";
			countdown--;

			setTimeout("doAutoSubmit();", 1000);
		}
	// ]]></script>';
}

?>