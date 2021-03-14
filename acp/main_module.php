<?php
/**
 *
 * OG Karma System. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Matt Ullman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace matt\karma\acp;

/**
 * OG Karma System ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user;

		$cfg_pfx =  'og_karma_system_';

		$user->add_lang_ext('matt/karma', 'common');
		$this->tpl_name = 'acp_demo_body';
		$this->page_title = $user->lang('ACP_KARMA_TITLE');
		add_form_key('karma/settings');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('karma/settings'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$config->set($cfg_pfx . 'display_user_id', $request->variable('karma_display_user_id', 1));
			$config->set($cfg_pfx . 'display_num_karma_given', $request->variable('karma_display_num_karma_given', 1));
			$config->set($cfg_pfx . 'display_num_smites_given', $request->variable('karma_display_num_smites_given', 1));
			$config->set($cfg_pfx . 'display_last_user_smite_given', $request->variable('karma_display_last_user_smite_given', 1));
			$config->set($cfg_pfx . 'display_last_user_smite_received', $request->variable('karma_display_last_user_smite_received', 1));

			$config->set($cfg_pfx . 'per_user_cooldown_minutes', $request->variable('karma_per_user_cooldown_minutes', (60 * 5)));

			$config->set($cfg_pfx . 'global_cooldown_minutes', $request->variable('karma_global_cooldown_minutes', (60 * 2)));
			$config->set($cfg_pfx . 'global_cooldown_count', $request->variable('karma_global_cooldown_count', 3));

			$config->set($cfg_pfx . 'mods_bypass_limits', $request->variable('karma_mods_bypass_limits', 0));
			$config->set($cfg_pfx . 'admins_bypass_limits', $request->variable('karma_admins_bypass_limits', 0));

			trigger_error($user->lang('ACP_KARMA_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$template->assign_vars([
			'U_ACTION'								 => $this->u_action,
			'KARMA_DISPLAY_USER_ID'					 => $config[$cfg_pfx . 'display_user_id'],
			'KARMA_DISPLAY_NUM_KARMA_GIVEN'			 => $config[$cfg_pfx . 'display_num_karma_given'],
			'KARMA_DISPLAY_NUM_SMITES_GIVEN' 		 => $config[$cfg_pfx . 'display_num_smites_given'],
			'KARMA_DISPLAY_LAST_USER_SMITE_GIVEN' 	 => $config[$cfg_pfx . 'display_last_user_smite_given'],
			'KARMA_DISPLAY_LAST_USER_SMITE_RECEIVED' => $config[$cfg_pfx . 'display_last_user_smite_received'],

			'KARMA_PER_USER_COOLDOWN_MINUTES'	=> $config[$cfg_pfx . 'per_user_cooldown_minutes'],
			'KARMA_GLOBAL_COOLDOWN_MINUTES'	    => $config[$cfg_pfx . 'global_cooldown_minutes'],
			'KARMA_GLOBAL_COOLDOWN_COUNT'	    => $config[$cfg_pfx . 'global_cooldown_count'],

			'ACP_KARMA_MODS_BYPASS_LIMITS'			 => $config[$cfg_pfx . 'mods_bypass_limits'],
			'ACP_KARMA_ADMINS_BYPASS_LIMITS' 		 => $config[$cfg_pfx . 'admins_bypass_limits'],
		]);
	}
}
