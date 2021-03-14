<?php
/**
 *
 * OG Karma System. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Matt Ullman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace matt\karma\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['og_karma_system_display_user_id']);
	}

	static public function depends_on()
	{
		return [
			'\phpbb\db\migration\data\v31x\v314',
			'\matt\karma\migrations\install_user_schema',
		];
	}

	public function update_data()
	{
		$prefix = 'og_karma_system_';
		return [
			['config.add',
				[$prefix . 'display_user_id', 1],
				[$prefix . 'display_num_karma_given', 1],
				[$prefix . 'display_num_smites_given', 1],
				[$prefix . 'display_last_user_smite_given', 1],
				[$prefix . 'display_last_user_smite_received', 1],

				// default to 5 hours
				[$prefix . 'per_user_cooldown_minutes', (60 * 5)],

				// default to 2 hours
				[$prefix . 'global_cooldown_minutes', (60 * 2)],

				// default to 3
				[$prefix . 'global_cooldown_count', 3],

				[$prefix . 'mods_bypass_limits', 0],
				[$prefix . 'admins_bypass_limits', 0],
			],

			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_KARMA_TITLE'
			]],
			['module.add', [
				'acp',
				'ACP_KARMA_TITLE',
				[
					'module_basename'	=> '\matt\karma\acp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}

	public function revert_data()
	{
		$prefix = 'og_karma_system_';
		return [
			['config.remove', [
				[$prefix . 'display_user_id', 1],
				[$prefix . 'display_num_karma_given', 1],
				[$prefix . 'display_num_smites_given', 1],
				[$prefix . 'display_last_user_smite_given', 1],
				[$prefix . 'display_last_user_smite_received', 1],
			]],
			['module.remove', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_KARMA_TITLE'
			]],
			['module.remove', [
				'acp',
				'ACP_KARMA_TITLE',
				[
					'module_basename'	=> '\matt\karma\acp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
