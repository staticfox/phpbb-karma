<?php
/**
 *
 * OG Karma System. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Matt Ullman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace matt\karma\controller;

/**
 * OG Karma System main controller.
 */
class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @ \php\database */
	protected $db;

	/* @var string DB table prefix */
	protected $table_prefix;

	/* @var \phpbb\request\request */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config
	 * @param \phpbb\controller\helper	$helper
	 * @param \phpbb\template\template	$template
	 * @param \phpbb\user				$user
	 * @param \php\database             $db
	 * @param \phpbb\request\request    $request
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		$table_prefix,
		\phpbb\request\request $request
	)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->table_prefix = $table_prefix;
		// $this->request = $request;
	}

	/**
	 * controller for /karma/{target_user_id}/{post_id}/{action}
	 *
	 * @param integer $target_user_id
	 * @param integer $post_id
	 * @param integer $action
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function handle($target_user_id, $post_id, $action)
	{
		$now = (int) time();

		$source_user_id = $this->db->sql_escape($this->user->data['user_id']);
		$target_user_id = $this->db->sql_escape((int)$target_user_id);

		$back_url = '/forums/viewtopic.php?p=' . $post_id . '#p' . $post_id;

		$this->template->assign_vars([
			'KARMA_LINK_ACTION'	=> $back_url,
			'KARMA_POST_ID'		=> $post_id,
			'L_BACK_TO_PREV'	=> $this->user->lang['BACK_TO_PREV']
		]);

		$action = (int)$action;

		// Only 0 and 1 are valid actions
		if ($action != 0 && $action != 1)
		{
			$this->template->assign_var('KARMA_MESSAGE', $this->user->lang('KARMA_ACTION_ERROR'));
			return $this->helper->render('karma_body.html');
		}

		// Make sure the target user exists
		$sql = 'SELECT username_clean
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . $target_user_id . ';';
		$result = $this->db->sql_query($sql);
		$username_clean = $this->db->sql_fetchfield('username_clean');
		$this->db->sql_freeresult($result);
		if (!$username_clean)
		{
			$this->template->assign_var('KARMA_MESSAGE', $this->user->lang('KARMA_ACTION_USER_NOT_FOUND'));
			return $this->helper->render('karma_body.html');
		}

		// Dont allow people to smite banned users
		$sql = 'SELECT COUNT(*) AS ban_count
				FROM ' . BANLIST_TABLE . '
				WHERE ban_userid = ' . $target_user_id . ' AND (ban_end = 0 OR ban_end > ' . $now . ');';
		$result = $this->db->sql_query($sql);
		$ban_count = (int) $this->db->sql_fetchfield('ban_count');
		$this->db->sql_freeresult($result);
		if ($ban_count > 0)
		{
			$this->template->assign_var('KARMA_MESSAGE', $this->user->lang('KARMA_ACTION_USER_IS_BANNED'));
			return $this->helper->render('karma_body.html');
		}

		// Make sure the post exists
		// TODO: Make sure the user has access to the post
		$sql = 'SELECT COUNT(*) AS post_count
				FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . $post_id . ';';
		$result = $this->db->sql_query($sql);
		$post_count = (int) $this->db->sql_fetchfield('post_count');
		$this->db->sql_freeresult($result);

		if ($post_count == 0)
		{
			$this->template->assign_vars([
				'KARMA_LINK_ACTION' => '/forums',
				'KARMA_MESSAGE' 	=> $this->user->lang('KARMA_ACTION_POST_NOT_FOUND')
			]);
			return $this->helper->render('karma_body.html');
		}

		// Check if the user is on a user-specific cooldown
		$user_cooldown_minutes = (int) $this->config['og_karma_system_per_user_cooldown_minutes'];
		if ($user_cooldown_minutes)
		{
			$sql = 'SELECT timestamp AS last_ts_for_user FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE source_user_id = ' . $source_user_id . ' AND target_user_id = ' . $target_user_id . '
				ORDER BY timestamp DESC LIMIT 1;';
			$result = $this->db->sql_query($sql);
			$last_ts_for_user = $this->db->sql_fetchfield('last_ts_for_user');
			$this->db->sql_freeresult($result);

			if ($last_ts_for_user)
			{
				$last_ts_for_user = (int) $last_ts_for_user;
			}
			else
			{
				$last_ts_for_user = 0;
			}

			if ($last_ts_for_user && $now - $last_ts_for_user < (60 * $user_cooldown_minutes))
			{
				$this->template->assign_var('KARMA_MESSAGE', $this->user->lang('KARMA_ACTION_COOLDOWN'));
				return $this->helper->render('karma_body.html');
			}
		}

		// Check if the user is on a global cooldown
		$global_cooldown_minutes = (int) $this->config['og_karma_system_global_cooldown_minutes'];
		$global_cooldown_count = (int) $this->config['og_karma_system_global_cooldown_count'];
		if($global_cooldown_count && $global_cooldown_minutes)
		{
			$sql = 'SELECT COUNT(*) AS recent_actions FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE source_user_id = ' . $source_user_id . ' AND (' . $now . ' - timestamp) < ' . ($global_cooldown_minutes * 60) . ';';
			$result = $this->db->sql_query($sql);
			$recent_actions = (int) $this->db->sql_fetchfield('recent_actions');
			$this->db->sql_freeresult($result);

			if ($recent_actions >= $global_cooldown_count)
			{
				$this->template->assign_var('KARMA_MESSAGE', $this->user->lang('KARMA_ACTION_COOLDOWN'));
				return $this->helper->render('karma_body.html');
			}
		}

		// Add the entry
		$sql = 'INSERT INTO ' . $this->table_prefix . 'og_karma_system_scoreboard
			(source_user_id, target_user_id, action, timestamp) VALUES ( ' .
			$source_user_id . ', ' . $target_user_id . ', ' . $action . ', ' . $now . ');';
		$result = $this->db->sql_query($sql);
		$this->db->sql_freeresult($result);

		$lang_message = $action == 0 ? 'KARMA_ACTION_USER_SMITED' : 'KARMA_ACTION_USER_APPLAUDED';

		$this->template->assign_var('KARMA_MESSAGE', $this->user->lang($lang_message, $username_clean));
		return $this->helper->render('karma_body.html');
	}
}
