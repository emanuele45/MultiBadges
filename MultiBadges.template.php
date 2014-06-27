<?php

/**
 * Multi Badges
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

function template_populate_multi_badge_posterarea($primary, $groups)
{
	global $context, $settings;
// _debug($groups);
	$return = $primary . '</li>';
	foreach($groups as $group)
	{
		if (!empty($group['icons']))
			$return .= '
							<li class="listlevel1 icons">' . str_repeat('<img src="' . str_replace('$language', $context['user']['language'], isset($group['icons'][1]) ? $settings['images_url'] . '/group_icons/' . $group['icons'][1] : '') . '" alt="*" />', empty($group['icons'][0]) || empty($group['icons'][1]) ? 0 : $group['icons'][0]) . '</li>';

		$return .= '
							<li class="listlevel1 membergroup">' . $group['group_name'] . '</li>';
	}
	return $return;
}