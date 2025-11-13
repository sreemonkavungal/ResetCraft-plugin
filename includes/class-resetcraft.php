<?php
/**
 * Core plugin functionality.
 *
 * @package ResetCraft
 */

namespace ResetCraft;

use ResetCraft\Admin\ResetCraft_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central plugin class responsible for orchestrating reset operations.
 */
class ResetCraft {

	/**
	 * Admin handler instance.
	 *
	 * @var ResetCraft_Admin
	 */
	protected $admin;

	/**
	 * Cached selective operations definition.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected $operations = [];

	/**
	 * Bootstraps the plugin.
	 */
	public function run() {
		$this->admin = new ResetCraft_Admin( $this );
		$this->admin->register_hooks();

		add_action( 'admin_post_resetcraft_full_reset', [ $this, 'handle_full_reset' ] );
		add_action( 'admin_post_resetcraft_selective_reset', [ $this, 'handle_selective_reset' ] );
	}

	/**
	 * Returns the list of selective reset operations.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_selective_operations() {
		if ( ! empty( $this->operations ) ) {
			return $this->operations;
		}

		$this->operations = [
			'content'    => [
				'label'       => __( 'Posts & Pages', 'resetcraft' ),
				'description' => __( 'Deletes all posts, pages, and custom post types (excluding media).', 'resetcraft' ),
				'callback'    => [ $this, 'reset_content' ],
			],
			'media'      => [
				'label'       => __( 'Media Library', 'resetcraft' ),
				'description' => __( 'Removes every attachment from the media library.', 'resetcraft' ),
				'callback'    => [ $this, 'reset_media' ],
			],
			'comments'   => [
				'label'       => __( 'Comments', 'resetcraft' ),
				'description' => __( 'Deletes all comments, pingbacks, and trackbacks.', 'resetcraft' ),
				'callback'    => [ $this, 'reset_comments' ],
			],
			'terms'      => [
				'label'       => __( 'Taxonomies', 'resetcraft' ),
				'description' => __( 'Removes categories, tags, and custom taxonomy terms (default category preserved).', 'resetcraft' ),
				'callback'    => [ $this, 'reset_terms' ],
			],
			'users'      => [
				'label'       => __( 'Users', 'resetcraft' ),
				'description' => __( 'Deletes all users except the current user and network administrators.', 'resetcraft' ),
				'callback'    => [ $this, 'reset_users' ],
			],
			'options'    => [
				'label'       => __( 'Options & Customizer', 'resetcraft' ),
				'description' => __( 'Clears non-essential options, widgets, and theme customizations.', 'resetcraft' ),
				'callback'    => [ $this, 'reset_options' ],
			],
			'transients' => [
				'label'       => __( 'Transients & Cache', 'resetcraft' ),
				'description' => __( 'Deletes all transients and flushes caches.', 'resetcraft' ),
				'callback'    => [ $this, 'reset_transients' ],
			],
		];

		return $this->operations;
	}

	/**
	 * Handles the one-click full reset action.
	 *
	 * @return void
	 */
	public function handle_full_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'resetcraft' ) );
		}

		check_admin_referer( 'resetcraft_full_reset' );

		$confirmation = isset( $_POST['resetcraft_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['resetcraft_confirmation'] ) ) : '';

		if ( 'RESET' !== strtoupper( $confirmation ) ) {
			$this->set_admin_notice( 'error', __( 'Confirmation phrase mismatch. Type RESET to continue.', 'resetcraft' ) );
			$this->safe_redirect();
			return;
		}

		$this->prepare_environment();

		$this->reset_content();
		$this->reset_media();
		$this->reset_comments();
		$this->reset_terms();
		$this->reset_users();
		$this->reset_options();
		$this->reset_transients();
		$this->create_default_content();
		$this->finalize_reset();

		/**
		 * Fires after the full reset routine completes.
		 *
		 * @param int $user_id Current user ID.
		 */
		do_action( 'resetcraft_after_full_reset', get_current_user_id() );

		$this->set_admin_notice( 'success', __( 'Full reset completed. Default content has been restored.', 'resetcraft' ) );
		$this->safe_redirect();
	}

	/**
	 * Handles selective reset submissions.
	 *
	 * @return void
	 */
	public function handle_selective_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'resetcraft' ) );
		}

		check_admin_referer( 'resetcraft_selective_reset' );

		$selected   = isset( $_POST['resetcraft_operations'] ) ? (array) wp_unslash( $_POST['resetcraft_operations'] ) : [];
		$operations = $this->get_selective_operations();

		if ( empty( $selected ) ) {
			$this->set_admin_notice( 'error', __( 'No reset operations were selected.', 'resetcraft' ) );
			$this->safe_redirect();
			return;
		}

		$confirmation = isset( $_POST['resetcraft_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['resetcraft_confirmation'] ) ) : '';

		if ( 'RESET' !== strtoupper( $confirmation ) ) {
			$this->set_admin_notice( 'error', __( 'Confirmation phrase mismatch. Type RESET to continue.', 'resetcraft' ) );
			$this->safe_redirect();
			return;
		}

		$this->prepare_environment();

		foreach ( $selected as $operation ) {
			if ( isset( $operations[ $operation ] ) && is_callable( $operations[ $operation ]['callback'] ) ) {
				call_user_func( $operations[ $operation ]['callback'] );
			}
		}

		$this->finalize_reset();

		/**
		 * Fires after selective reset operations complete.
		 *
		 * @param array $selected Operation keys that were executed.
		 */
		do_action( 'resetcraft_after_selective_reset', $selected );

		$this->set_admin_notice( 'success', __( 'Selected reset operations completed successfully.', 'resetcraft' ) );
		$this->safe_redirect();
	}

	/**
	 * Schedules a redirect back to the dashboard page.
	 *
	 * @return void
	 */
	protected function safe_redirect() {
		wp_safe_redirect( admin_url( 'admin.php?page=resetcraft' ) );
		exit;
	}

	/**
	 * Ensure the environment can handle heavy operations.
	 *
	 * @return void
	 */
	protected function prepare_environment() {
		if ( ! function_exists( 'wp_raise_memory_limit' ) ) {
			require_once ABSPATH . 'wp-includes/load.php';
		}

		wp_raise_memory_limit( 'admin' );

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		wp_suspend_cache_invalidation( true );
	}

	/**
	 * Performs post-reset cleanup tasks.
	 *
	 * @return void
	 */
	protected function finalize_reset() {
		wp_suspend_cache_invalidation( false );
		wp_cache_flush();
		flush_rewrite_rules();
	}

	/**
	 * Deletes all posts, pages, and custom post types (except attachments).
	 *
	 * @return void
	 */
	public function reset_content() {
		$post_types = get_post_types(
			[
				'show_ui' => true,
			],
			'names'
		);

		unset( $post_types['attachment'] );

		$post_ids = get_posts(
			[
				'post_type'      => $post_types,
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'suppress_filters' => true,
			]
		);

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Deletes all media attachments.
	 *
	 * @return void
	 */
	public function reset_media() {
		$attachments = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'suppress_filters' => true,
			]
		);

		foreach ( $attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	/**
	 * Deletes every comment, pingback, and trackback.
	 *
	 * @return void
	 */
	public function reset_comments() {
		$comment_ids = get_comments(
			[
				'status' => 'all',
				'fields' => 'ids',
				'number' => 0,
			]
		);

		foreach ( $comment_ids as $comment_id ) {
			wp_delete_comment( $comment_id, true );
		}
	}

	/**
	 * Removes taxonomy terms while preserving the default category.
	 *
	 * @return void
	 */
	public function reset_terms() {
		$taxonomies    = get_taxonomies(
			[
				'show_ui' => true,
			],
			'names'
		);
		$default_cat   = (int) get_option( 'default_category', 0 );
		$protected_ids = array_filter( [ $default_cat ] );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				]
			);

			foreach ( $terms as $term_id ) {
				if ( in_array( (int) $term_id, $protected_ids, true ) ) {
					continue;
				}

				wp_delete_term( $term_id, $taxonomy );
			}
		}
	}

	/**
	 * Deletes all non-critical users and reassigns content.
	 *
	 * @return void
	 */
	public function reset_users() {
		$current_user_id = get_current_user_id();
		$ids             = get_users(
			[
				'fields' => 'ids',
			]
		);

		foreach ( $ids as $user_id ) {
			if ( (int) $user_id === (int) $current_user_id ) {
				continue;
			}

			if ( is_multisite() && is_super_admin( $user_id ) ) {
				continue;
			}

			wp_delete_user( $user_id, $current_user_id );
		}
	}

	/**
	 * Clears options, widgets, and theme mods while preserving core settings.
	 *
	 * @return void
	 */
	public function reset_options() {
		global $wpdb;

		$protected = [
			'blogname',
			'blogdescription',
			'siteurl',
			'home',
			'admin_email',
			'template',
			'stylesheet',
			'current_theme',
			'timezone_string',
			'date_format',
			'time_format',
			'start_of_week',
			'WPLANG',
			'permalink_structure',
			'db_version',
			'resetcraft_notice',
		];

		$options = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s",
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_' ) . '%'
			)
		);

		foreach ( $options as $option_name ) {
			if ( in_array( $option_name, $protected, true ) ) {
				continue;
			}

			delete_option( $option_name );
		}

		remove_theme_mods();
		update_option( 'sidebars_widgets', [] );
		delete_option( 'widget_recent-posts' );
		delete_option( 'widget_recent-comments' );
		delete_option( 'widget_categories' );
		delete_option( 'widget_archives' );
		delete_option( 'widget_meta' );
		delete_option( 'widget_search' );
		delete_option( 'widget_tag_cloud' );
		delete_option( 'widget_text' );
		delete_option( 'widget_custom_html' );
		delete_option( 'widget_media_image' );

		update_option( 'blogdescription', __( 'Just another WordPress site', 'resetcraft' ) );
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', 0 );
		update_option( 'page_for_posts', 0 );
	}

	/**
	 * Deletes all transients and flushes the object cache.
	 *
	 * @return void
	 */
	public function reset_transients() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_' ) . '%'
			)
		);

		wp_cache_flush();
	}

	/**
	 * Recreates default WordPress demo content.
	 *
	 * @return void
	 */
	protected function create_default_content() {
		$user_id = get_current_user_id();

		$hello_world_id = wp_insert_post(
			[
				'post_title'     => __( 'Hello world!', 'resetcraft' ),
				'post_content'   => __( 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!', 'resetcraft' ),
				'post_status'    => 'publish',
				'post_author'    => $user_id,
				'post_type'      => 'post',
				'comment_status' => 'open',
				'ping_status'    => 'open',
				'post_name'      => sanitize_title( _x( 'Hello world!', 'Default post slug', 'resetcraft' ) ),
			]
		);

		wp_insert_post(
			[
				'post_title'     => __( 'Sample Page', 'resetcraft' ),
				'post_content'   => __( 'This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation.', 'resetcraft' ),
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => $user_id,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_name'      => sanitize_title( _x( 'Sample Page', 'Default page slug', 'resetcraft' ) ),
				'page_template'  => 'default',
			]
		);

		if ( $hello_world_id && ! is_wp_error( $hello_world_id ) ) {
			wp_insert_comment(
				[
					'comment_post_ID'      => $hello_world_id,
					'comment_author'       => __( 'A WordPress Commenter', 'resetcraft' ),
					'comment_author_email' => 'wapuu@wordpress.org',
					'comment_content'      => __( 'Hi, this is a comment. To get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.', 'resetcraft' ),
					'comment_author_url'   => 'https://wordpress.org/',
					'comment_approved'     => 1,
				]
			);
		}
	}

	/**
	 * Stores an admin notice to display on next page load.
	 *
	 * @param string $type    Notice type: success|warning|error|info.
	 * @param string $message Notice message.
	 *
	 * @return void
	 */
	public function set_admin_notice( $type, $message ) {
		set_transient(
			'resetcraft_notice',
			[
				'type'    => $type,
				'message' => wp_kses_post( $message ),
			],
			60
		);
	}

	/**
	 * Retrieves and clears the pending admin notice.
	 *
	 * @return array{type:string,message:string}|null
	 */
	public function pop_admin_notice() {
		$notice = get_transient( 'resetcraft_notice' );

		if ( $notice ) {
			delete_transient( 'resetcraft_notice' );
			return $notice;
		}

		return null;
	}
}

