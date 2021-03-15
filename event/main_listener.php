<?php
/**
 *
 * OG Karma System. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Matt Ullman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace matt\karma\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OG Karma System Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'				=> 'load_language_on_setup',
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',
		);
	}

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \php\database */
	protected $db;

	/* @var string phpEx */
	protected $php_ext;

	/* @var string DB table prefix */
	protected $table_prefix;

	/* @var \phpbb\config\config */
	protected $config;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user               $user       User object
	 * @param \phpbb\db\driver\driver_interface	$db
	 * @param string                    $php_ext    phpEx
	 * @param \phpbb\config\config		$config
	 */
	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		$php_ext,
		$table_prefix,
		\phpbb\config\config $config
	)
	{
		$this->helper   = $helper;
		$this->template = $template;
		$this->user     = $user;
		$this->db       = $db;
		// $this->php_ext  = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->config = $config;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'matt/karma',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Appends the Karma related fields to the
	 * user's profile within the post's row.
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function viewtopic_modify_post_row($event)
	{
		$now = (int) time();
		$post_row = $event['post_row'];
		$poster_id = $post_row['POSTER_ID'];

		$sql = 'SELECT ((
			SELECT COUNT(*)
				FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE target_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 1) - (
			SELECT COUNT(*)
				FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE target_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 0)
		) AS karma_score;';

		$result = $this->db->sql_query($sql);
		$post_row['KARMA_SCORE'] = $this->db->sql_fetchfield('karma_score');
		$this->db->sql_freeresult($result);

		// '$POSTER_ID.$APPLAUDS_GIVEN.$SMITES_GIVEN.$LAST_SMITE_GIVEN_TO_ID.$LAST_SMITE_RECEIVED_FROM_ID'
		$karma_info = "";

		$tbl_pfx = 'og_karma_system_display_';

		// poster id
		if ($this->config[$tbl_pfx . 'user_id'])
		{
			$karma_info .= $post_row['POSTER_ID'] . '.';
		}

		$this->db->sql_transaction('begin');

		// karmas given
		if ($this->config[$tbl_pfx . 'num_karma_given'])
		{
			$sql = 'SELECT COUNT(*) AS karmas_given FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE source_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 1';
			$result = $this->db->sql_query($sql);
			$karmas_given = $this->db->sql_fetchfield('karmas_given');
			$this->db->sql_freeresult($result);
			$karma_info .= $karmas_given . '.';
		}

		// karmas taken
		if ($this->config[$tbl_pfx . 'num_smites_given'])
		{
			$sql = 'SELECT COUNT(*) AS karmas_taken FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE source_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 0';
			$result = $this->db->sql_query($sql);
			$karmas_taken = $this->db->sql_fetchfield('karmas_taken');
			$this->db->sql_freeresult($result);
			$karma_info .= $karmas_taken . '.';
		}

		// last smite given to
		if ($this->config[$tbl_pfx . 'last_user_smite_given'])
		{
			$sql = 'SELECT target_user_id AS smite_given_to FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE source_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 0 ORDER BY timestamp DESC LIMIT 1;';
			$result = $this->db->sql_query($sql);
			$smite_given_to = $this->db->sql_fetchfield('smite_given_to');
			$this->db->sql_freeresult($result);
			if (!$smite_given_to)
				$smite_given_to = '0';
			$karma_info .= $smite_given_to . '.';
		}

		// last smite received from
		if ($this->config[$tbl_pfx . 'last_user_smite_received'])
		{
			$sql = 'SELECT source_user_id AS smite_received_from FROM ' . $this->table_prefix . 'og_karma_system_scoreboard
				WHERE target_user_id = ' . $post_row['POSTER_ID'] . '
				AND action = 0 ORDER BY timestamp DESC LIMIT 1;';
			$result = $this->db->sql_query($sql);
			$smite_received_from = $this->db->sql_fetchfield('smite_received_from');
			$this->db->sql_freeresult($result);
			if (!$smite_received_from)
				$smite_received_from = '0';
			$karma_info .= $smite_received_from . '.';
		}

		$this->db->sql_transaction('rollback');

		$karma_info = trim($karma_info, '.');

		$post_row['KARMA_INFOLINE'] = $karma_info;

		// Don't allow anonymous voting
		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			$post_row['KARMA_HIDE_CONTROLS_AND_INFO'] = true;
			goto finish;
		}

		// Don't allow people to smite/applaud themselves
		if ($post_row['POSTER_ID'] == $this->user->data['user_id'])
		{
			$post_row['KARMA_IS_SELF'] = true;
			goto finish;
		}

		// Don't allow people to smite banned users
		$is_banned = false;
		$sql = 'SELECT COUNT(*) AS ban_count
				FROM ' . BANLIST_TABLE . '
				WHERE ban_userid = ' . $post_row['POSTER_ID'] . ' AND (ban_end = 0 OR ban_end > ' . $now . ');';
		$result = $this->db->sql_query($sql);
		$ban_count = (int) $this->db->sql_fetchfield('ban_count');
		$this->db->sql_freeresult($result);
		if ($ban_count > 0)
		{
			$post_row['KARMA_HIDE_CONTROLS_AND_INFO'] = true;
			goto finish;
		}

		// User is allow to applaud/smite this person
		$post_row['U_APPLAUD_USER']	= $this->helper->route('matt_karma_controller', [
			'target_user_id' => $post_row['POSTER_ID'],
			'post_id' => $post_row['POST_ID'],
			'action' => 1,
		]);

		$post_row['U_SMITE_USER'] = $this->helper->route('matt_karma_controller', [
			'target_user_id' => $post_row['POSTER_ID'],
			'post_id' => $post_row['POST_ID'],
			'action' => 0,
		]);

// Jump label so we can re-assign post_row and bail
finish:
		$event['post_row'] = $post_row;
	}
}
