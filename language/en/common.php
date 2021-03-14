<?php
/**
 *
 * OG Karma System. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Matt Ullman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'KARMA_ACTION_ERROR'	=> 'an error has occured',
	'KARMA_ACTION_COOLDOWN'	=> 'you must wait until you can applaud/smite again',
	'KARMA_ACTION_USER_NOT_FOUND' => 'user not found',
	'KARMA_ACTION_USER_IS_BANNED' => 'cannot applaud/smite a banned user',
	'KARMA_ACTION_POST_NOT_FOUND' => 'post not found',

	'KARMA_ACTION_USER_APPLAUDED'		=> 'applauded %s successfully',
	'KARMA_ACTION_USER_SMITED'		=> 'smited %s successfully',

	'ACP_KARMA'					=> 'Settings',

	'ACP_KARMA_DISPLAY_USER_ID'                  => 'Display user id',
	'ACP_KARMA_DISPLAY_NUM_KARMA_GIVEN'          => 'Display # karmas given',
	'ACP_KARMA_DISPLAY_NUM_SMITES_GIVEN'         => 'Display # smites given',
	'ACP_KARMA_DISPLAY_LAST_USER_SMITE_GIVEN'    => 'Display last user id of smite given to',
	'ACP_KARMA_DISPLAY_LAST_USER_SMITE_RECEIVED' => 'Display last user id of smite received from',

	'ACP_KARMA_PER_USER_COOL_DOWN_MINS' => 'Per-user cooldown in minutes',
	'ACP_KARMA_GLOBAL_COOL_DOWN_MINS' => 'Global cooldown in minutes',
	'ACP_KARMA_GLOBAL_COOL_DOWN_COUNT' => '# of actions allowed within the global timeframe',

	'ACP_KARMA_MODS_BYPASS_LIMITS' => '[TODO] Whether or not mods can bypass all limits',
	'ACP_KARMA_ADMINS_BYPASS_LIMITS' => '[TODO] Whether or not admins can bypass all limits',

	'ACP_KARMA_SETTING_SAVED'	=> 'Settings have been saved successfully!',
));
