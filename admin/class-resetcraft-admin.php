<?php
/**
 * Admin-facing functionality.
 *
 * @package ResetCraft
 */

namespace ResetCraft\Admin;

use ResetCraft\ResetCraft;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin hooks and dashboard rendering.
 */
class ResetCraft_Admin {

	/**
	 * Core plugin instance.
	 *
	 * @var ResetCraft
	 */
	protected $plugin;

	/**
	 * Dashboard slug.
	 *
	 * @var string
	 */
	protected $menu_slug = 'resetcraft';

	/**
	 * Constructor.
	 *
	 * @param ResetCraft $plugin Core plugin instance.
	 */
	public function __construct( ResetCraft $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Adds the ResetCraft dashboard menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'ResetCraft', 'resetcraft' ),
			__( 'ResetCraft', 'resetcraft' ),
			'manage_options',
			$this->menu_slug,
			[ $this, 'render_dashboard' ],
			'dashicons-update-alt',
			3
		);
	}

	/**
	 * Enqueues admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_resetcraft' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'resetcraft-admin',
			RESETCRAFT_PLUGIN_URL . 'assets/css/admin.css',
			[],
			RESETCRAFT_VERSION
		);

		wp_enqueue_script(
			'resetcraft-admin',
			RESETCRAFT_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			RESETCRAFT_VERSION,
			true
		);

		wp_localize_script(
			'resetcraft-admin',
			'resetcraftAdmin',
			[
				'confirmFullReset' => __( 'This will remove all content and restore defaults. Type RESET in the confirmation field and press OK to continue.', 'resetcraft' ),
				'confirmSelective' => __( 'Selected data will be permanently deleted. Type RESET in the confirmation field and press OK to continue.', 'resetcraft' ),
			]
		);
	}

	/**
	 * Renders admin notices stored by the plugin.
	 *
	 * @return void
	 */
	public function render_notices() {
		$notice = $this->plugin->pop_admin_notice();

		if ( empty( $notice ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice['type'] ),
			wp_kses_post( $notice['message'] )
		);
	}

	/**
	 * Outputs the dashboard page markup.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$operations = $this->plugin->get_selective_operations();
		?>
		<div class="wrap resetcraft-wrap">
			<header class="resetcraft-header">
				<div>
					<h1><?php esc_html_e( 'ResetCraft Control Center', 'resetcraft' ); ?></h1>
					<p><?php esc_html_e( 'Effortlessly reset your WordPress environment with fine-grained control. Choose between a full rebuild or targeted cleanups.', 'resetcraft' ); ?></p>
				</div>
				<div class="resetcraft-meta">
					<span class="resetcraft-tag"><?php esc_html_e( 'Version', 'resetcraft' ); ?> <?php echo esc_html( RESETCRAFT_VERSION ); ?></span>
					<span class="resetcraft-tag"><?php esc_html_e( 'Professional Dashboard', 'resetcraft' ); ?></span>
				</div>
			</header>

			<div class="resetcraft-grid">
				<section class="resetcraft-card resetcraft-card--danger">
					<h2><?php esc_html_e( 'One-Click Full Reset', 'resetcraft' ); ?></h2>
					<p><?php esc_html_e( 'Restore WordPress to a clean slate with default demo content. Ideal for developers needing a fresh start.', 'resetcraft' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="resetcraft-form resetcraft-form--full">
						<?php wp_nonce_field( 'resetcraft_full_reset' ); ?>
						<input type="hidden" name="action" value="resetcraft_full_reset" />

						<label class="resetcraft-confirmation">
							<span><?php esc_html_e( 'Type RESET to confirm', 'resetcraft' ); ?></span>
							<input type="text" name="resetcraft_confirmation" placeholder="<?php esc_attr_e( 'RESET', 'resetcraft' ); ?>" required />
						</label>

						<button type="submit" class="button button-primary button-hero resetcraft-submit resetcraft-submit--danger">
							<span class="dashicons dashicons-backup"></span>
							<?php esc_html_e( 'Execute Full Reset', 'resetcraft' ); ?>
						</button>
					</form>

					<ul class="resetcraft-bullets">
						<li><?php esc_html_e( 'Clears posts, pages, media, comments, and users.', 'resetcraft' ); ?></li>
						<li><?php esc_html_e( 'Recreates default WordPress starter content.', 'resetcraft' ); ?></li>
						<li><?php esc_html_e( 'Resets options, widgets, and theme customizations.', 'resetcraft' ); ?></li>
						<li><?php esc_html_e( 'Flushes caches and rewrites.', 'resetcraft' ); ?></li>
					</ul>
				</section>

				<section class="resetcraft-card">
					<h2><?php esc_html_e( 'Selective Resets', 'resetcraft' ); ?></h2>
					<p><?php esc_html_e( 'Pick exactly what you need to clean up. Combine operations for precise control over your reset workflow.', 'resetcraft' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="resetcraft-form resetcraft-form--selective">
						<?php wp_nonce_field( 'resetcraft_selective_reset' ); ?>
						<input type="hidden" name="action" value="resetcraft_selective_reset" />

						<div class="resetcraft-options">
							<?php foreach ( $operations as $key => $operation ) : ?>
								<label class="resetcraft-option">
									<input type="checkbox" name="resetcraft_operations[]" value="<?php echo esc_attr( $key ); ?>" />
									<span class="resetcraft-option__title"><?php echo esc_html( $operation['label'] ); ?></span>
									<span class="resetcraft-option__description"><?php echo esc_html( $operation['description'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<label class="resetcraft-confirmation">
							<span><?php esc_html_e( 'Type RESET to confirm', 'resetcraft' ); ?></span>
							<input type="text" name="resetcraft_confirmation" placeholder="<?php esc_attr_e( 'RESET', 'resetcraft' ); ?>" required />
						</label>

						<button type="submit" class="button button-secondary button-hero resetcraft-submit">
							<span class="dashicons dashicons-controls-repeat"></span>
							<?php esc_html_e( 'Run Selected Operations', 'resetcraft' ); ?>
						</button>
					</form>

					<div class="resetcraft-tip">
						<strong><?php esc_html_e( 'Tip', 'resetcraft' ); ?>:</strong>
						<?php esc_html_e( 'Combine cache clears with content wipes to ensure a pristine starting point.', 'resetcraft' ); ?>
					</div>
				</section>
			</div>

			<section class="resetcraft-card resetcraft-card--info resetcraft-footer">
				<h2><?php esc_html_e( 'Best Practices & Safeguards', 'resetcraft' ); ?></h2>
				<ul class="resetcraft-bullets">
					<li><?php esc_html_e( 'Always create a backup before running destructive operations.', 'resetcraft' ); ?></li>
					<li><?php esc_html_e( 'Only administrators can trigger resets, protecting production environments.', 'resetcraft' ); ?></li>
					<li><?php esc_html_e( 'Confirmation prompts prevent accidental clicks from executing resets.', 'resetcraft' ); ?></li>
					<li><?php esc_html_e( 'Operations run server-side to maintain reliability and auditability.', 'resetcraft' ); ?></li>
				</ul>
			</section>
		</div>
		<?php
	}
}

