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

	/* @var \phpbb\user */
	protected $user;

	/* @ \php\database */
	protected $db;

	/* @ \phpbb\auth\auth */
	protected $auth;

	/* @var string DB table prefix */
	protected $table_prefix;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config
	 * @param \phpbb\user				$user
	 * @param \php\database             $db
	 * @param \phpbb\auth\auth          $auth
	 * @param string                    $table_prefix
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\auth\auth $auth,
		$table_prefix
	)
	{
		$this->config = $config;
		$this->user = $user;
		$this->db = $db;
		$this->auth = $auth;
		$this->table_prefix = $table_prefix;
	}

	private function get_return_link($url)
	{
		return '<br /><br /><a href="' . $url . ' ">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a><br /><br />';
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

		// Make sure the post exists. We do this first since it's important for
		// redirect purposes.
		$sql = 'SELECT poster_id, forum_id
				FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . $post_id . ' AND poster_id = ' . $target_user_id . ';';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		$poster_id = (int) $row['poster_id'];
		$forum_id = (int) $row['forum_id'];

		// Make sure the post exists, the target is the person that made the post
		// and the current user has access to view the forum where the post lives.
		if (!$poster_id || $target_user_id != $poster_id || !$this->auth->acl_get('f_read', $forum_id))
		{
			$back_url = '/forums';
			trigger_error($this->user->lang('KARMA_ACTION_POST_NOT_FOUND') . $this->get_return_link($back_url));
		}

		// Don't allow anonymous votes
		if ($source_user_id == ANONYMOUS)
		{
			trigger_error($this->user->lang('KARMA_ACTION_NO_ANONYMOUS_VOTES') . $this->get_return_link($back_url));
		}

		// Don't allow users to vote for themselves
		if ($source_user_id == $target_user_id)
		{
			trigger_error($this->user->lang('KARMA_ACTION_CANT_SELF_VOTE') . $this->get_return_link($back_url));
		}

		// Only 0 and 1 are valid actions
		$action = (int)$action;
		if ($action != 0 && $action != 1)
		{
			trigger_error($this->user->lang('KARMA_ACTION_ERROR') . $this->get_return_link($back_url));
		}

		// Make sure the target user exists
		$sql = 'SELECT username
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . $target_user_id . ';';
		$result = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);
		if (!$username)
		{
			// I don't think this will ever hit
			trigger_error($this->user->lang('KARMA_ACTION_USER_NOT_FOUND') . $this->get_return_link($back_url));
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
			trigger_error($this->user->lang('KARMA_ACTION_USER_IS_BANNED') . $this->get_return_link($back_url));
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
			if ($last_ts_for_user && ($now - (int) $last_ts_for_user) < (60 * $user_cooldown_minutes))
			{
				trigger_error($this->user->lang('KARMA_ACTION_COOLDOWN') . $this->get_return_link($back_url));
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
				trigger_error($this->user->lang('KARMA_ACTION_COOLDOWN') . $this->get_return_link($back_url));
			}
		}

		// Add the entry
		$sql = 'INSERT INTO ' . $this->table_prefix . 'og_karma_system_scoreboard
			(source_user_id, target_user_id, action, timestamp) VALUES ( ' .
			$source_user_id . ', ' . $target_user_id . ', ' . $action . ', ' . $now . ');';
		$result = $this->db->sql_query($sql);
		$this->db->sql_freeresult($result);

		$lang_message = $action == 0 ? 'KARMA_ACTION_USER_SMITED' : 'KARMA_ACTION_USER_APPLAUDED';
		trigger_error($this->user->lang($lang_message, $username) . $this->get_return_link($back_url));
	}
}
