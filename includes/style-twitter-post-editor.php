<?php
/**
 * In post tweet editor for edit flow. Allows a user to create a post and add the body and image for that promotional tweet
 * inside that post edit page. The tweet will be published when the post id published.
 * Likewise the tweet body should be added to the post's edit flow calender hover pagelet.
 * User: jbetancourt
 * Date: 11/20/18
 * Time: 3:00 PM
 */

class STYLE_TWITTER_POST_EDITOR {
	// Hold the class instance.

	// ###################### Class setup ########################

	private static $instance = null;

	private $data = array();

	function __construct() {
		$this->hc_stpe_setup_variables();
		$this->hc_stpe_actions_and_filters();
	}

	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new STYLE_TWITTER_POST_EDITOR();
		}

		return self::$instance;
	}

	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	public function get ($param) {
		return $this->$param;
	}

	// ###################### Plugin starts here ########################

	public function hc_stpe_setup_variables () {
		$this->prefix = "hc_stpe_";
		$this->tweet_field = $this->prefix."txt_tweet";
	}

	public function hc_stpe_actions_and_filters () {

	}

	public function hc_stpe_post_metabox_add () {

	}

	public function hc_stpe_twitter_editor () {
		wp_editor( "", $this->tweet_field, array(
			'wpautop'             => false,
			'media_buttons'       => false,
			'default_editor'      => 'quicktags',
			'drag_drop_upload'    => false,
			'textarea_name'       => $this->tweet_field,
			'textarea_rows'       => 20,
			'tabindex'            => '',
			'tabfocus_elements'   => ':prev,:next',
			'editor_css'          => '',
			'editor_class'        => '',
			'teeny'               => true,
			'dfw'                 => false,
			'_content_editor_dfw' => false,
			'tinymce'             => false,
			'quicktags'           => true
		) );
	}

	public function hc_stpe_post_editor_js_css () {

	}

	public function hc_stpe_post_metabox_save ($post_id) {
		if(current_user_can('edit_post')) {
			$this->post_id = $post_id;
			$this->tweet_field_value = !empty($_POST[$this->tweet_field ]) ?sanitize_text_field($this->tweet_field):null;
			$this->hc_stpe_modify_tweet_metadata();
		} else {
			error_log("Tweet Save Rejected: User cannot update post");
		}
		return $post_id;
	}

	private function hc_stpe_modify_tweet_metadata ($value = false) {
		$value = $value?:$this->tweet_field_value;
		return update_post_meta( $this->post_id, $this->tweet_field, $value);
	}

	private function hc_stpe_delete_tweet_metadata () {
		return delete_post_meta( $this->post_id, $this->tweet_field);
	}

	public function hc_stpe_on_published($post_id) {
		$this->hc_stpe_send_tweet_to_twitter($post_id);
	}

	public function hc_stpe_add_tweet_to_edit_flow_calendar($post_id) {

	}

	public function hc_stpe_get_tweet_from_post($post_id) {
		get_post_meta($post_id, $this->tweet_field, true );
	}

	public function hc_stpe_send_tweet_to_twitter($tweet) {
		$test =
	}
}
STYLE_TWITTER_POST_EDITOR::getInstance();