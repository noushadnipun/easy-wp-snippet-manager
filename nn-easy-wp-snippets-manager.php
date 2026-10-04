<?php
/**
 * Plugin Name:       Easy WP Snippets Manager
 * Description:       Paste any code (HTML, CSS, JavaScript or PHP) and choose where it runs. The code type is detected automatically.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Noushad Nipun
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nn-easy-wp-snippets-manager
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Emergency switch: add this line to wp-config.php to disable all PHP snippets.
 * define( 'EWPSM_DISABLE_PHP', true );
 */

define( 'EWPSM_VERSION', '1.0.0' );

class EWPSM_Plugin {

	const CPT = 'ewpsm_snippet';

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post_' . self::CPT, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_filter( 'manage_' . self::CPT . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'out_head' ), 99 );
		add_action( 'wp_body_open', array( $this, 'out_body' ), 99 );
		add_action( 'wp_footer', array( $this, 'out_footer' ), 99 );
		add_action( 'admin_head', array( $this, 'out_admin_head' ), 99 );
		add_action( 'admin_footer', array( $this, 'out_admin_footer' ), 99 );

		add_action( 'plugins_loaded', array( $this, 'run_php_snippets' ), 1 );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'nn-easy-wp-snippets-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/* ------------------------------------------------------ Post type */

	public function register_cpt() {
		register_post_type( self::CPT, array(
			'labels'          => array(
				'name'               => __( 'Snippets', 'nn-easy-wp-snippets-manager' ),
				'singular_name'      => __( 'Snippet', 'nn-easy-wp-snippets-manager' ),
				'add_new'            => __( 'Add New', 'nn-easy-wp-snippets-manager' ),
				'add_new_item'       => __( 'Add New Snippet', 'nn-easy-wp-snippets-manager' ),
				'edit_item'          => __( 'Edit Snippet', 'nn-easy-wp-snippets-manager' ),
				'all_items'          => __( 'All Snippets', 'nn-easy-wp-snippets-manager' ),
				'menu_name'          => __( 'Snippets', 'nn-easy-wp-snippets-manager' ),
				'not_found'          => __( 'No snippets found.', 'nn-easy-wp-snippets-manager' ),
				'not_found_in_trash' => __( 'No snippets found in Trash.', 'nn-easy-wp-snippets-manager' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-editor-code',
			'menu_position'   => 80,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
				'create_posts'       => 'manage_options',
			),
			'map_meta_cap'    => false,
		) );
	}

	/* ------------------------------------------------------ Options */

	private function type_labels() {
		return array(
			'html' => __( 'HTML', 'nn-easy-wp-snippets-manager' ),
			'css'  => __( 'CSS', 'nn-easy-wp-snippets-manager' ),
			'js'   => __( 'JavaScript', 'nn-easy-wp-snippets-manager' ),
			'php'  => __( 'PHP', 'nn-easy-wp-snippets-manager' ),
		);
	}

	private function locations() {
		return array(
			'head'   => __( 'Header', 'nn-easy-wp-snippets-manager' ),
			'body'   => __( 'Top of body ', 'nn-easy-wp-snippets-manager' ),
			'footer' => __( 'Footer', 'nn-easy-wp-snippets-manager' ),
		);
	}

	private function scopes() {
		return array(
			'frontend' => __( 'Website', 'nn-easy-wp-snippets-manager' ),
			'admin'    => __( 'Admin dashboard', 'nn-easy-wp-snippets-manager' ),
			'both'     => __( 'Website and admin dashboard', 'nn-easy-wp-snippets-manager' ),
		);
	}

	/* ------------------------------------------------------ Detection */

	/**
	 * Detect code type: html, css, js or php.
	 */
	private function detect_type( $code ) {
		$c = trim( (string) $code );
		if ( '' === $c ) {
			return 'html';
		}

		// Explicit PHP tag at the start.
		if ( preg_match( '/^<\?(php)?\s/i', $c ) ) {
			return 'php';
		}

		// Any HTML tag (including <style> and <script>) means "output as is".
		if ( preg_match( '/<\s*\/?\s*[a-z][a-z0-9-]*(\s[^>]*)?>/i', $c ) || false !== strpos( $c, '<!--' ) ) {
			return 'html';
		}

		// PHP / WordPress specific patterns.
		$php = '/(?:\b(?:add|remove|do|apply)_(?:action|filter)\s*\(|\badd_shortcode\s*\(|\bregister_[a-z_]+\s*\(|\bwp_[a-z_]+\s*\(|\b(?:get|update|delete)_option\s*\(|\bis_admin\s*\(|\bdefine\s*\(|\$this->|\bnamespace\s+\w|^\s*\$[a-zA-Z_]\w*\s*(?:=|\.=)|^\s*echo\s|^\s*(?:if|foreach)\s*\(.*\$\w+)/m';
		if ( preg_match( $php, $c ) ) {
			return 'php';
		}

		// JavaScript patterns.
		$js = '/\b(?:function|var|let|const)\b|\bdocument\.|\bwindow\.|\bconsole\.|addEventListener|\bjQuery\b|\$\s*\(|=>/';
		if ( preg_match( $js, $c ) ) {
			return 'js';
		}

		// CSS: selector { property: value; }
		if ( preg_match( '/[^{}]+\{\s*[a-z-]+\s*:[^{}]*\}/i', $c ) || preg_match( '/^\s*@(media|import|font-face|keyframes)/i', $c ) ) {
			return 'css';
		}

		return 'html';
	}

	private function effective_type( $code, $override ) {
		return ( $override && isset( $this->type_labels()[ $override ] ) ) ? $override : $this->detect_type( $code );
	}

	/* ------------------------------------------------------ Meta box */

	public function add_metabox() {
		add_meta_box( 'ewpsm_box', __( 'Snippet', 'nn-easy-wp-snippets-manager' ), array( $this, 'render_metabox' ), self::CPT, 'normal', 'high' );
	}

	public function render_metabox( $post ) {
		wp_nonce_field( 'ewpsm_save', 'ewpsm_nonce' );

		$override = get_post_meta( $post->ID, '_ewpsm_override', true );
		$loc      = get_post_meta( $post->ID, '_ewpsm_location', true ) ?: 'head';
		$scope    = get_post_meta( $post->ID, '_ewpsm_scope', true ) ?: 'frontend';
		$active   = get_post_meta( $post->ID, '_ewpsm_active', true );
		$code     = get_post_meta( $post->ID, '_ewpsm_code', true );
		$labels   = $this->type_labels();

		if ( '' === $active && 'auto-draft' === $post->post_status ) {
			$active = '1';
		}
		?>
		<div class="ewpsm-topbar">
			<label for="ewpsm_override"><?php esc_html_e( 'Code type', 'nn-easy-wp-snippets-manager' ); ?></label>
			<select name="ewpsm_override" id="ewpsm_override">
				<option value=""><?php esc_html_e( 'Auto-detect', 'nn-easy-wp-snippets-manager' ); ?></option>
				<?php foreach ( $labels as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $override, $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="ewpsm-badge" id="ewpsm_badge"></span>
		</div>

		<div class="ewpsm-code">
			<textarea name="ewpsm_code" id="ewpsm_code" rows="16" placeholder="<?php esc_attr_e( 'Paste your HTML, CSS, JavaScript or PHP code here.', 'nn-easy-wp-snippets-manager' ); ?>"><?php echo esc_textarea( $code ); ?></textarea>
		</div>

		<div class="ewpsm-row">
			<div class="ewpsm-field" id="ewpsm_place_wrap">
				<label for="ewpsm_location"><?php esc_html_e( 'Placement', 'nn-easy-wp-snippets-manager' ); ?></label>
				<select name="ewpsm_location" id="ewpsm_location">
					<?php foreach ( $this->locations() as $k => $v ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $loc, $k ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ewpsm-field">
				<label for="ewpsm_scope"><?php esc_html_e( 'Show on', 'nn-easy-wp-snippets-manager' ); ?></label>
				<select name="ewpsm_scope" id="ewpsm_scope">
					<?php foreach ( $this->scopes() as $k => $v ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $scope, $k ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ewpsm-field">
				<span class="ewpsm-label"><?php esc_html_e( 'Status', 'nn-easy-wp-snippets-manager' ); ?></span>
				<label><input type="checkbox" name="ewpsm_active" value="1" <?php checked( $active, '1' ); ?>> <?php esc_html_e( 'Active', 'nn-easy-wp-snippets-manager' ); ?></label>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------ Save */

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['ewpsm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ewpsm_nonce'] ) ), 'ewpsm_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Raw code is intentional: only administrators can save it.
		$code     = isset( $_POST['ewpsm_code'] ) ? wp_unslash( $_POST['ewpsm_code'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$loc      = isset( $_POST['ewpsm_location'] ) ? sanitize_key( wp_unslash( $_POST['ewpsm_location'] ) ) : 'head';
		$scope    = isset( $_POST['ewpsm_scope'] ) ? sanitize_key( wp_unslash( $_POST['ewpsm_scope'] ) ) : 'frontend';
		$override = isset( $_POST['ewpsm_override'] ) ? sanitize_key( wp_unslash( $_POST['ewpsm_override'] ) ) : '';
		$active   = ! empty( $_POST['ewpsm_active'] ) ? '1' : '0';

		if ( ! isset( $this->locations()[ $loc ] ) ) {
			$loc = 'head';
		}
		if ( ! isset( $this->scopes()[ $scope ] ) ) {
			$scope = 'frontend';
		}
		if ( ! isset( $this->type_labels()[ $override ] ) ) {
			$override = '';
		}

		$type = $this->effective_type( $code, $override );

		delete_transient( 'ewpsm_error_' . get_current_user_id() );
		if ( 'php' === $type && '1' === $active ) {
			$error = $this->check_php_syntax( $code );
			if ( $error ) {
				$active = '0';
				set_transient( 'ewpsm_error_' . get_current_user_id(), $error, 60 );
			}
		}

		update_post_meta( $post_id, '_ewpsm_code', $code );
		update_post_meta( $post_id, '_ewpsm_override', $override );
		update_post_meta( $post_id, '_ewpsm_type', $type );
		update_post_meta( $post_id, '_ewpsm_location', $loc );
		update_post_meta( $post_id, '_ewpsm_scope', $scope );
		update_post_meta( $post_id, '_ewpsm_active', $active );
		delete_post_meta( $post_id, '_ewpsm_runtime_error' );
	}

	private function clean_php( $code ) {
		$code = preg_replace( '/^\s*<\?(?:php)?\s*/i', '', (string) $code );
		$code = preg_replace( '/\s*\?>\s*$/', '', $code );
		return $code;
	}

	private function check_php_syntax( $code ) {
		try {
			token_get_all( "<?php\n" . $this->clean_php( $code ), TOKEN_PARSE );
		} catch ( \Throwable $e ) {
			/* translators: 1: line number, 2: error message */
			return sprintf( __( 'Syntax error on line %1$d: %2$s', 'nn-easy-wp-snippets-manager' ), max( 1, $e->getLine() - 1 ), $e->getMessage() );
		}
		return '';
	}

	public function admin_notices() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::CPT !== $screen->post_type ) {
			return;
		}

		$err = get_transient( 'ewpsm_error_' . get_current_user_id() );
		if ( $err ) {
			delete_transient( 'ewpsm_error_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'The snippet was saved but not activated.', 'nn-easy-wp-snippets-manager' ) . '</strong> ' . esc_html( $err ) . '</p></div>';
		}

		global $post;
		if ( 'post' === $screen->base && $post ) {
			$rt = get_post_meta( $post->ID, '_ewpsm_runtime_error', true );
			if ( $rt ) {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'This snippet caused an error and was deactivated automatically.', 'nn-easy-wp-snippets-manager' ) . '</strong> ' . esc_html( $rt ) . '</p></div>';
			}
		}
	}

	/* ------------------------------------------------------ Admin UI */

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || self::CPT !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		$deps     = array( 'jquery' );
		if ( false !== $settings ) {
			$deps[] = 'code-editor';
		}

		wp_enqueue_style( 'ewpsm-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), EWPSM_VERSION );
		wp_enqueue_script( 'ewpsm-admin', plugins_url( 'assets/admin.js', __FILE__ ), $deps, EWPSM_VERSION, true );

		$cfg = array(
			'labels'   => $this->type_labels(),
			/* translators: %s: detected code type, e.g. CSS */
			'detected' => __( 'Detected: %s', 'nn-easy-wp-snippets-manager' ),
			'settings' => false === $settings ? null : $settings,
		);
		wp_add_inline_script( 'ewpsm-admin', 'window.ewpsmConfig = ' . wp_json_encode( $cfg ) . ';', 'before' );
	}

	public function columns( $cols ) {
		return array(
			'cb'       => $cols['cb'],
			'title'    => __( 'Title', 'nn-easy-wp-snippets-manager' ),
			'ewpsm_type'=> __( 'Type', 'nn-easy-wp-snippets-manager' ),
			'ewpsm_loc' => __( 'Runs', 'nn-easy-wp-snippets-manager' ),
			'ewpsm_on'  => __( 'Status', 'nn-easy-wp-snippets-manager' ),
			'date'     => __( 'Date', 'nn-easy-wp-snippets-manager' ),
		);
	}

	public function column_content( $col, $post_id ) {
		$type   = get_post_meta( $post_id, '_ewpsm_type', true ) ?: 'html';
		$labels = $this->type_labels();

		switch ( $col ) {
			case 'ewpsm_type':
				echo esc_html( isset( $labels[ $type ] ) ? $labels[ $type ] : $type );
				break;
			case 'ewpsm_loc':
				$scope = get_post_meta( $post_id, '_ewpsm_scope', true ) ?: 'frontend';
				$scopes = $this->scopes();
				if ( 'php' !== $type ) {
					$loc  = get_post_meta( $post_id, '_ewpsm_location', true ) ?: 'head';
					$locs = $this->locations();
					echo esc_html( isset( $locs[ $loc ] ) ? $locs[ $loc ] : $loc ) . '<br>';
				}
				echo esc_html( isset( $scopes[ $scope ] ) ? $scopes[ $scope ] : $scope );
				break;
			case 'ewpsm_on':
				echo '1' === get_post_meta( $post_id, '_ewpsm_active', true )
					? '<span style="color:#00a32a;font-weight:600">' . esc_html__( 'Active', 'nn-easy-wp-snippets-manager' ) . '</span>'
					: '<span style="color:#b32d2e">' . esc_html__( 'Inactive', 'nn-easy-wp-snippets-manager' ) . '</span>';
				break;
		}
	}

	/* ------------------------------------------------------ Query */

	private function get_snippets( $types ) {
		static $cache = null;
		if ( null === $cache ) {
			$cache = array();
			$posts = get_posts( array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_key'       => '_ewpsm_active', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => '1',            // phpcs:ignore WordPress.DB.SlowDBQuery
			) );
			foreach ( $posts as $p ) {
				$cache[] = array(
					'id'    => $p->ID,
					'title' => $p->post_title,
					'type'  => get_post_meta( $p->ID, '_ewpsm_type', true ) ?: 'html',
					'loc'   => get_post_meta( $p->ID, '_ewpsm_location', true ) ?: 'head',
					'scope' => get_post_meta( $p->ID, '_ewpsm_scope', true ) ?: 'frontend',
					'code'  => get_post_meta( $p->ID, '_ewpsm_code', true ),
				);
			}
		}
		return array_filter( $cache, function ( $s ) use ( $types ) {
			return in_array( $s['type'], $types, true );
		} );
	}

	private function scope_ok( $scope, $is_admin ) {
		return 'both' === $scope || ( $is_admin ? 'admin' === $scope : 'frontend' === $scope );
	}

	/* ------------------------------------------------------ Output */

	private function output( $location, $is_admin ) {
		foreach ( $this->get_snippets( array( 'html', 'css', 'js' ) ) as $s ) {
			if ( $s['loc'] !== $location || ! $this->scope_ok( $s['scope'], $is_admin ) ) {
				continue;
			}
			$code = trim( $s['code'] );
			if ( '' === $code ) {
				continue;
			}
			echo "\n<!-- EWPSM: " . esc_html( str_replace( '--', '', $s['title'] ) ) . " -->\n";
			if ( 'css' === $s['type'] && false === stripos( $code, '<style' ) ) {
				echo "<style>\n" . $code . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
			} elseif ( 'js' === $s['type'] && false === stripos( $code, '<script' ) ) {
				echo "<script>\n" . $code . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}

	public function out_head()         { $this->output( 'head', false ); }
	public function out_body()         { $this->output( 'body', false ); }
	public function out_footer()       { $this->output( 'footer', false ); }
	public function out_admin_head()   { $this->output( 'head', true ); }
	public function out_admin_footer() { $this->output( 'footer', true ); }

	/* ------------------------------------------------------ PHP */

	public function run_php_snippets() {
		if ( defined( 'EWPSM_DISABLE_PHP' ) && EWPSM_DISABLE_PHP ) {
			return;
		}
		$is_admin = is_admin();
		foreach ( $this->get_snippets( array( 'php' ) ) as $s ) {
			if ( $this->scope_ok( $s['scope'], $is_admin ) ) {
				$this->run_one( $s );
			}
		}
	}

	private function run_one( $s ) {
		$code = $this->clean_php( $s['code'] );
		if ( $this->check_php_syntax( $code ) ) {
			$this->deactivate( $s['id'], __( 'Syntax error.', 'nn-easy-wp-snippets-manager' ) );
			return;
		}
		try {
			eval( $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		} catch ( \Throwable $e ) {
			/* translators: 1: error message, 2: line number */
			$this->deactivate( $s['id'], sprintf( __( '%1$s (line %2$d)', 'nn-easy-wp-snippets-manager' ), $e->getMessage(), $e->getLine() ) );
		}
	}

	private function deactivate( $id, $msg ) {
		update_post_meta( $id, '_ewpsm_active', '0' );
		update_post_meta( $id, '_ewpsm_runtime_error', $msg );
	}
}

new EWPSM_Plugin();
