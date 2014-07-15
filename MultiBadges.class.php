<?php

/**
 * Multi Badges
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.1.0
 */

class MultiBadges
{
	/**
	 * The instance of the class
	 * @var MultiBadges
	 */
	private static $_instance = null;

	/**
	 * Array of groups previously loaded
	 * @var array
	 */
	private $_loadedGroups = array();

	/**
	 * Array of templates already created
	 * @var string[]
	 */
	private $_populatedTemplates = array();

	/**
	 * Used to remember if the template was loaded or not
	 * @var bool
	 */
	private $_template_loaded = false;

	/**
	 * This adds the additional_groups field to the load_member_data query
	 * using the integrate_load_member_data hook
	 *
	 * See loadMemberData for details on the arguments
	 *
	 * @param string $select_columns the SELECT statement
	 * @param string $select_tables the list of JOIN'ed tables
	 * @param string $set what set of data was requested
	 */
	public static function tweak_query(&$select_columns, &$select_tables, $set)
	{
		if ($set !== 'profile')
			$select_columns .= ', mem.additional_groups';
	}

	/**
	 * Loads the groups for some members, used in integrate_add_member_data
	 *
	 * See loadMemberData for details on the arguments
	 *
	 * @param int[] $new_loaded_ids An array of member ids
	 * @param string $set what set of data was requested
	 */
	public static function load_groups($new_loaded_ids, $set)
	{
		MultiBadges::instance()->load($new_loaded_ids);
	}

	/**
	 * Saving settings?
	 */
	public static function save_membergroup_settings()
	{
		global $modSettings;

		$mb_settings = array();

		if (isset($_POST['mb_membergroups']))
		{
			foreach ($_POST['mb_membergroups'] as $id => $status)
			{
				if ($status == 'on')
					$mb_settings[] = (int) $id;
			}
		}

		updateSettings(array('mb_settings' => serialize($mb_settings)));
	}

	/**
	 * Adds the settings
	 *
	 * @param mixed[] $config_vars
	 */
	public static function modify_membergroup_settings(&$config_vars)
	{
		global $txt, $context, $modSettings;

		loadLanguage('MultiBadges');
		loadTemplate('MultiBadges');

		$context['mb_settings'] = !empty($modSettings['mb_settings']) ? @unserialize($modSettings['mb_settings']) : array();

		require_once(SUBSDIR . '/Membergroups.subs.php');
		$mb_membergroups = getBasicMembergroupData(array('all'));

		// This is necessary for some missing options in getBasicMembergroupData (1.0 note)
		{
			$postgroups = getBasicMembergroupData('postgroups');
			$postgroup_ids = array();
			foreach ($postgroups as $val)
				$postgroup_ids[] = $val['id'];
		}

		$context['mb_membergroups'] = array();
		foreach ($mb_membergroups as $id => $val)
		{
			$context['mb_membergroups'][$id] = $val;
			$context['mb_membergroups'][$id]['is_postgroup'] = in_array($val['id'], $postgroup_ids);
			$context['mb_membergroups'][$id]['status'] = in_array($id, $context['mb_settings']) ? 'on' : 'off';
		}

		$config_vars[] = '';
		$config_vars[] = array(
			'callback',
			'multi_badge_settings',
		);
	}

	/**
	 * Prepares the template to show the groups.
	 * Is attached to the integrate_prepare_display_context hook
	 * When a corresponding hook will be present in the PM area
	 * it will be attached to that as well.
	 *
	 * See prepareDisplayContext_callback for details on the arguments
	 *
	 * @param mixed[] $output A mess of data regarding the current message
	 * @param mixed[] $message A mess of data regarding the current message
	 */
	public static function display_groups(&$output, &$message)
	{
		// @todo options go here
		$output['member']['group'] = MultiBadges::instance()->userGroupContext($output['member']['id'], $output['member']['group']);
	}

	/**
	 * Takes care of calling the internal function that queries the db
	 *
	 * @param int[] $new_loaded_ids An array of member ids
	 */
	public function load($new_loaded_ids)
	{
		$this->_queryGroups($new_loaded_ids);
	}

	/**
	 * This does a bit of things, but in fact its duty is to return the piece
	 * of relevant template
	 *
	 * @param int $id A member id
	 * @param string $primary The current primary membergroup
	 *
	 * @return string the template
	 */
	public function userGroupContext($id, $primary)
	{
		global $context;

		if (!$this->_template_loaded)
		{
			loadTemplate('MultiBadges');
			$this->_template_loaded = true;
		}

		if (isset($this->_loadedGroups[$id]))
		{
			if (!isset($this->_populatedTemplates[$id]))
				$this->_populatedTemplates[$id] = template_populate_multi_badge_posterarea($primary, $this->_loadedGroups[$id]);
		}
		else
			$this->_populatedTemplates[$id] = $primary;

		return $this->_populatedTemplates[$id];
	}

	/**
	 * Does all the magic: retrieves the additional groups for a number of members
	 * and prepares the result of the query to be used later by the template
	 *
	 * @param int[] $ids An array of member ids
	 */
	private function _queryGroups($ids)
	{
		// this sounds awful, we should do something about all these globals...
		global $user_profile, $modSettings;

		$to_load = array();
		$user_groups = array();
		foreach ($ids as $id)
		{
			$user_groups[$id] = array_map('intval', array_map('trim', explode(',', $user_profile[$id]['additional_groups'])));
			$to_load += $user_groups[$id];
		}

		$to_load = array_diff(array_unique($to_load), array(0));

		if (empty($to_load))
			return;

		$db = database();
		$request = $db->query('', '
			SELECT id_group, group_name, icons
			FROM {db_prefix}membergroups
			WHERE id_group IN ({array_int:groups})
				AND hidden != {int:hidden}',
			array(
				'groups' => $to_load,
				'hidden' => 2,
			)
		);

		$to_display = !empty($modSettings['mb_settings']) ? @unserialize($modSettings['mb_settings']) : array();

		while ($row = $db->fetch_assoc($request))
		{
			$row['icons'] = explode('#', $row['icons']);
			foreach ($user_groups as $id => $groups)
			{
				foreach ($groups as $group)
				{
					if ($row['id_group'] == $group && in_array($group, $to_display))
					{
						$this->_loadedGroups[$id][] = $row;
					}
				}
			}
		}
		$db->free_result($request);
	}

	/**
	 * This class is a singletone, but I'm not yet sure why I did it that way...
	 * Probably just because it was funny.
	 */
	public static function instance()
	{
		if (self::$_instance === null)
			self::$_instance = new MultiBadges();

		return self::$_instance;
	}
}