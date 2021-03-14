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
 * OG Karma System ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\matt\karma\acp\main_module',
			'title'		=> 'ACP_KARMA_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_KARMA',
					'auth'	=> 'ext_matt/karma && acl_a_board',
					'cat'	=> array('ACP_KARMA_TITLE')
				),
			),
		);
	}
}
