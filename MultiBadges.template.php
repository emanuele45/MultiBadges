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

function template_callback_multi_badge_settings()
{
	global $context, $modSettings, $txt;

	$context['current_permission'] = 'mb_membergroups';
	$context['member_groups'] = $context['mb_membergroups'];

	// Some overrides necessary to workaround a function that should be improved
	$old_setting = $modSettings['permission_enable_deny'];
	$modSettings['permission_enable_deny'] = false;
	$old_txt = $txt['avatar_select_permission'];
	$txt['avatar_select_permission'] = $txt['mb_membergroups_legend'];

	echo '
					<dl class="settings">
						<dt>
							<a id="setting_mb_membergroups"></a> <span><label for="mb_membergroups">', $txt['mb_membergroups'], '</label></span><br />
						</dt>
						<dd>';
	template_inline_permissions();

	echo '
						</dd>';

	$modSettings['permission_enable_deny'] = $old_setting;
	$txt['avatar_select_permission'] = $old_txt;
}
