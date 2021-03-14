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
	'ACP_KARMA_TITLE'			=> 'Karma Module',
));
