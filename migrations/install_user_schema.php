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

class install_user_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists(
			$this->table_prefix . 'og_karma_system_scoreboard', 'id'
		);
	}

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v31x\v314'];
	}

	public function update_schema()
	{
		return [
			'add_tables'		=> [
				 // Primary table where all karma/smite entries will be stored
				$this->table_prefix . 'og_karma_system_scoreboard' => [
					'COLUMNS' => [
						// Identifies the primary key from the database
						'id'			 => ['UINT', null, 'auto_increment'],

						// The user who applauded/smited us
						'source_user_id' => ['UINT', 0],

						// The user who we are applauding/smiting
						'target_user_id' => ['UINT', 0],

						// 0 smite
						// 1 applaud
						'action'	 => ['USINT', 1],

						// Whether or not the action bypassed the cooldown
						//
						// Typically, only mods and admins can do this
						'mod_abused'	 => ['USINT', 0],

						// The time when this action occurred
						'timestamp' 	 => ['UINT', 0],
					]
				]
			]
		];
	}

	public function revert_schema()
	{
		return [
			// Delete the stats table.
			'drop_tables'		=> [
				$this->table_prefix . 'og_karma_system_scoreboard',
			],
		];
	}
}
