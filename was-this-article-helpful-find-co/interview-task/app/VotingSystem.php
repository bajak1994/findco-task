<?php

namespace App;

/**
 * Class VotingSystem
 *
 * @package App
 */
class VotingSystem {
	public function __construct() {
		// Display the voting interface on the content of single posts
		add_action('the_content', [$this, 'displayVoting']);

		// Enqueue scripts and styles for the plugin
		add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueScriptsAdmin']);

		// Add AJAX actions for handling voting
		add_action('wp_ajax_simple_voting', [$this, 'ajaxHandler']);
		add_action('wp_ajax_nopriv_simple_voting', [$this, 'ajaxHandler']);

		// Add meta box for displaying voting results in the post editor
		add_action('add_meta_boxes',  [$this, 'addVotingResultsMetaBox']);
	}

	/**
	 * Enqueue admin styles
	 */
	public function enqueueScriptsAdmin() {
		wp_enqueue_style('admin-styles', dirname(plugin_dir_url(__FILE__)) . '/dist/admin.css', array(), '1.0.0', 'all');
	}

	/**
	 * Enqueue scripts and styles for the frontend
	 */
	public function enqueueScripts() {
		global $post;
	
		// Initial data to pass to JavaScript
		$initialData = array(
			'nonce' => wp_create_nonce('simple_voting_nonce'),
			'post_id' => $post->ID,
			'has_voted' => $this->hasUserVoted($post->ID),
			'user_vote' => $this->getUserVote($post->ID),
			'yes_votes' => $this->getVoteCount($post->ID, true),
			'no_votes' => $this->getVoteCount($post->ID, false),
			'ajax_url' => admin_url('admin-ajax.php'),
		);

		// Provide translations
		$translation_array = array(
			'thank_you_feedback' => __('THANK YOU FOR YOUR FEEDBACK.', FINDCO_TEXT_DOMAIN),
			'was_article_helpful' => __('WAS THIS ARTICLE HELPFUL?', FINDCO_TEXT_DOMAIN),
		);

		// Enqueue scripts and localize variables
		wp_enqueue_script('simple-voting-bundle', dirname(plugin_dir_url(__FILE__)) . '/dist/index.js', array(), '1.0', true);
		wp_enqueue_style('simple-voting-styles', dirname(plugin_dir_url(__FILE__)) . '/dist/index.css', array(), '1.0');
		wp_localize_script('simple-voting-bundle', 'simple_voting_ajax', $initialData);
		wp_localize_script('simple-voting-bundle', 'simple_voting_translations', $translation_array);
	}

	/**
	 * Check if the user has already voted for a post
	 */
	private function hasUserVoted($postId) {
		global $wpdb;

		$userIp = hash('sha256', filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP));

		$hasVoted = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND user_ip = %s",
			$postId,
			$userIp
		));

		return $hasVoted > 0;
	}

	/**
	 * Get the user's vote for a post
	 */
	private function getUserVote($postId) {
		global $wpdb;

		$userIp = hash('sha256', filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP));

		$hasVoted = $wpdb->get_var($wpdb->prepare(
			"SELECT vote_option FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND user_ip = %s",
			$postId,
			$userIp
		));

		return $hasVoted > 0;
	}
	
	/**
	 * Get the count of votes for a specific option (yes or no) for a post
	 */
	private function getVoteCount($postId, $voteOption) {
		global $wpdb;

		$voteCount = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND vote_option = %d",
			$postId,
			$voteOption ? 1 : 0
		));

		return intval($voteCount);
	}

	/**
	 * Display the voting interface on single post content
	 */
	public function displayVoting($content) {
		if (is_single()) {
			$content .= '<div id="simple-voting-react-root"></div>';
		}

		return $content;
	}

	/**
	 * Handle AJAX requests for voting
	 */
	public function ajaxHandler() {
		if ( ! wp_doing_ajax() ) {
			wp_die( esc_html( __( 'This method can only be called via AJAX.', FINDCO_TEXT_DOMAIN ) ) );
		}

		$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
		$vote = filter_input(INPUT_POST, 'vote', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
		$userIp = hash('sha256', filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP));

		// Check nonce
		if (check_ajax_referer('simple_voting_nonce', 'security', false) === false) {
			wp_send_json_error( esc_html( __( 'Something went wrong. Please, try again later.', FINDCO_TEXT_DOMAIN ) ) );
		}

		// Check if variables are valid
		if (false === $postId || false === $vote || false === $userIp) {
			wp_send_json_error( esc_html( __( 'Something went wrong. Please, try again later.', FINDCO_TEXT_DOMAIN ) ) );
		}

		global $wpdb;

		$hasVoted = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND user_ip = %s",
			$postId,
			$userIp
		));

		// If the user hasn't voted, insert their vote into the database
		if (!$hasVoted) {
			$result = $wpdb->insert(
				FINDCO_TABLE_NAME,
				array(
					'post_id' => $postId,
					'user_ip' => $userIp,
					'vote_option' => $vote,
				),
				array('%d', '%s', '%s')
			);

			if ($result === false) {
				error_log('Error inserting vote into the database. MySQL Error: ' . $wpdb->last_error);
			}
		}

		// Get updated vote counts
		$yesVotes = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND vote_option = 1",
			$postId
		));

		$noVotes = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . FINDCO_TABLE_NAME . " WHERE post_id = %d AND vote_option = 0",
			$postId
		));

		// Calculate percentages and prepare response
		$totalVotes = $yesVotes + $noVotes;
		$percentageYes = ($totalVotes > 0) ? ($yesVotes / $totalVotes) * 100 : 0;
		$percentageNo = ($totalVotes > 0) ? ($noVotes / $totalVotes) * 100 : 0;

		$response = array(
			'percentage_yes' => $percentageYes,
			'percentage_no' => $percentageNo,
			'total_votes' => $totalVotes,
			'yes_votes' => $yesVotes,
			'no_votes' => $noVotes,
		);

		wp_send_json($response);
	}

	/**
	 * Add meta box for displaying voting results in the post editor
	 */
	public function addVotingResultsMetaBox() {
		add_meta_box(
			'voting_results_meta_box',
			'Voting Results',
			[$this, 'displayVotingResultsMetaBox'],
			'post',
			'side',
			'default'
		);
	}

	/**
	 * Display voting results in the post editor meta box
	 */
	public function displayVotingResultsMetaBox($post) {
		$postId = $post->ID;
		$yesVotes = $this->getVoteCount($postId, true);
		$noVotes = $this->getVoteCount($postId, false);
		$totalVotes = $yesVotes + $noVotes;
	
		// Output voting results HTML
		?>
		<div class="findco-simple-voting-admin">
			<p class="findco-simple-voting-admin__votes findco-simple-voting-admin__votes--yes"> <?php echo esc_html($yesVotes); ?></p>
			<p class="findco-simple-voting-admin__votes findco-simple-voting-admin__votes--no"> <?php echo esc_html($noVotes); ?></p>
			<p><?php esc_html_e( 'Total Votes:', FINDCO_TEXT_DOMAIN ); ?> <?php echo esc_html($totalVotes); ?></p>
		</div>
		<?php
	}
}
