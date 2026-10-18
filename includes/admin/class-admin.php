<?php

namespace SchemaFrameworks\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Registry;

class Admin {

	private $config;

	private $registry;

	private $settings;

	private $schema_configuration;

	private $page_editor;

	private $post_type_mappings;

	private $post_content_editor;

	public function __construct( $config, Registry $registry ) {

		$this->config  = $config;
		$this->registry = $registry;

		$this->schema_configuration = new Schema_Configuration(
			$this->registry
		);

		$this->settings = new Settings(
			$this->config,
			$this->registry,
			$this->schema_configuration
		);

		$this->page_editor = new Page_Editor(
			$this->config,
			$this->registry,
			$this->schema_configuration
		);

		$this->post_type_mappings = new Post_Type_Mappings( $this->config, $this->registry );
		$this->post_content_editor = new Post_Content_Editor( $this->config );
	}

	public function init() {

		$this->settings->init();
		$this->page_editor->init();
		$this->post_content_editor->init();
		add_action( 'admin_menu', array( $this->post_type_mappings, 'register_menu' ), 20 );
		add_action( 'admin_post_schema_frameworks_save_post_mappings', array( $this->post_type_mappings, 'handle_save' ) );

		add_action(
			'admin_menu',
			array( $this, 'register_menu' )
		);
		add_action( 'admin_menu', array( $this, 'register_developer_support_menu' ), 30 );

		add_action( 'admin_post_schema_frameworks_refresh_diagnostics', array( $this, 'handle_refresh_diagnostics' ) );
		add_action( 'admin_post_schema_frameworks_export_diagnostics', array( $this, 'handle_export_diagnostics' ) );
		add_action( 'wp_ajax_schema_frameworks_scan_start', array( $this, 'ajax_scan_start' ) );
		add_action( 'wp_ajax_schema_frameworks_scan_next', array( $this, 'ajax_scan_next' ) );
		add_action( 'wp_ajax_schema_frameworks_scan_progress', array( $this, 'ajax_scan_progress' ) );
		add_action( 'wp_ajax_schema_frameworks_scan_control', array( $this, 'ajax_scan_control' ) );

		add_action(
			'schema_frameworks_graph_validation',
			array( $this, 'capture_graph_validation' ),
			10,
			5
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);
	}

	public function register_menu() {

		add_menu_page(
			'Schema Frameworks',
			'Schema Frameworks',
			'manage_options',
			'schema-frameworks',
			array( $this, 'render_dashboard_page' ),
			'dashicons-networking',
			80
		);


		add_submenu_page(
			'schema-frameworks',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'schema-frameworks-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'schema-frameworks',
			'General',
			'General',
			'manage_options',
			'schema-frameworks-general',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'schema-frameworks',
			'Modules',
			'Modules',
			'manage_options',
			'schema-frameworks-modules',
			array( $this, 'render_modules_page' )
		);

		add_submenu_page(
			'schema-frameworks',
			'Organization',
			'Organization',
			'manage_options',
			'schema-frameworks-organization',
			array( $this, 'render_organization_page' )
		);

		add_submenu_page(
			'schema-frameworks',
			'Entity Graph Debugger',
			'Entity Graph Debugger',
			'manage_options',
			'schema-frameworks-debugger',
			array( $this, 'render_graph_debugger_page' )
		);

		add_submenu_page(
			'schema-frameworks',
			'Graph Validation',
			'Graph Validation',
			'manage_options',
			'schema-frameworks-validation',
			array( $this, 'render_graph_validation_page' )
		);
		remove_submenu_page( 'schema-frameworks', 'schema-frameworks' );
	}

	public function register_developer_support_menu() {
		add_submenu_page(
			'schema-frameworks',
			'Developer Support',
			'Developer Support',
			'manage_options',
			'schema-frameworks-support',
			array( $this, 'render_developer_support_page' )
		);
	}

	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view Schema Frameworks diagnostics.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}

		$snapshots = $this->get_diagnostic_snapshots();
		$metadata = $this->registry->get_all_metadata();
		$enabled_modules = 0;
		foreach ( $metadata as $module_id => $item ) {
			if ( (bool) $this->config->get( 'modules.' . $module_id, false ) ) { ++$enabled_modules; }
		}
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$mappings = $this->config->get( 'post_type_mappings', array() );
		$mappings = is_array( $mappings ) ? $mappings : array();
		$confirmed = 0;
		foreach ( $mappings as $mapping ) { if ( is_array( $mapping ) && 'confirmed' === ( $mapping['status'] ?? '' ) ) { ++$confirmed; } }
		$issues = 0;
		$stale = 0;
		$diagnosed_post_ids = array();
		$diagnosed_by_type = array();
		$schema_type_counts = array();
		$relationship_count = 0;
		$entity_count = 0;
		$cutoff = time() - DAY_IN_SECONDS;
		foreach ( $snapshots as $snapshot ) {
			$issues += (int) ( $snapshot['error_count'] ?? 0 );
			if ( empty( $snapshot['captured_at_ts'] ) || (int) $snapshot['captured_at_ts'] < $cutoff ) { ++$stale; }
			if ( ! empty( $snapshot['post_id'] ) ) { $diagnosed_post_ids[ (int) $snapshot['post_id'] ] = true; }
			$relationship_count += (int) ( $snapshot['relationship_count'] ?? 0 );
			$entity_count += (int) ( $snapshot['entity_count'] ?? $snapshot['node_count'] ?? 0 );
			$snapshot_types = isset( $snapshot['schema_types'] ) && is_array( $snapshot['schema_types'] ) ? $snapshot['schema_types'] : array();
			if ( empty( $snapshot_types ) && ! empty( $snapshot['nodes'] ) && is_array( $snapshot['nodes'] ) ) {
				foreach ( $snapshot['nodes'] as $legacy_node ) { if ( ! empty( $legacy_node['type'] ) ) { $snapshot_types[] = $legacy_node['type']; } }
				$snapshot_types = array_values( array_unique( $snapshot_types ) );
			}
			foreach ( $snapshot_types as $schema_type ) {
				if ( is_string( $schema_type ) && '' !== $schema_type ) { $schema_type_counts[ $schema_type ] = ( $schema_type_counts[ $schema_type ] ?? 0 ) + 1; }
			}
		}
		$total_published_public = 0;
		$post_type_coverage = array();
		foreach ( $post_types as $post_type_name => $post_type_object ) {
			$status_counts = wp_count_posts( $post_type_name );
			$published_count = is_object( $status_counts ) ? (int) ( $status_counts->publish ?? 0 ) : 0;
			$total_published_public += $published_count;
			$post_type_coverage[ $post_type_name ] = array(
				'label' => $post_type_object->labels->singular_name ?? $post_type_name,
				'published' => $published_count,
				'diagnosed' => (int) ( $diagnosed_by_type[ $post_type_name ] ?? 0 ),
			);
		}
		$diagnosed_public_count = 0;
		foreach ( array_keys( $diagnosed_post_ids ) as $diagnosed_id ) {
			$diagnosed_post = get_post( $diagnosed_id );
			if ( $diagnosed_post && 'publish' === $diagnosed_post->post_status && is_post_type_viewable( $diagnosed_post->post_type ) && isset( $post_types[ $diagnosed_post->post_type ] ) ) {
				++$diagnosed_public_count;
				$type_key = sanitize_key( $diagnosed_post->post_type );
				$diagnosed_by_type[ $type_key ] = ( $diagnosed_by_type[ $type_key ] ?? 0 ) + 1;
			}
		}
		$coverage_percent = $total_published_public > 0 ? min( 100, round( ( $diagnosed_public_count / $total_published_public ) * 100, 1 ) ) : 0;
		arsort( $schema_type_counts );
		$latest = get_transient( 'schema_frameworks_graph_validation_latest' );

		$all_snapshots = $snapshots;
		$search_term = isset( $_GET['sf_snapshot_search'] ) ? sanitize_text_field( wp_unslash( $_GET['sf_snapshot_search'] ) ) : '';
		$filter_post_type = isset( $_GET['sf_snapshot_type'] ) ? sanitize_key( wp_unslash( $_GET['sf_snapshot_type'] ) ) : '';
		$filter_status = isset( $_GET['sf_snapshot_status'] ) ? sanitize_key( wp_unslash( $_GET['sf_snapshot_status'] ) ) : 'all';
		$filter_freshness = isset( $_GET['sf_snapshot_freshness'] ) ? sanitize_key( wp_unslash( $_GET['sf_snapshot_freshness'] ) ) : 'all';
		$sort_order = isset( $_GET['sf_snapshot_sort'] ) ? sanitize_key( wp_unslash( $_GET['sf_snapshot_sort'] ) ) : 'recent';
		$current_page = isset( $_GET['sf_snapshot_page'] ) ? max( 1, absint( $_GET['sf_snapshot_page'] ) ) : 1;
		if ( ! in_array( $filter_status, array( 'all', 'errors', 'clear' ), true ) ) { $filter_status = 'all'; }
		if ( ! in_array( $filter_freshness, array( 'all', 'recent', 'stale' ), true ) ) { $filter_freshness = 'all'; }
		if ( ! in_array( $sort_order, array( 'recent', 'oldest', 'errors' ), true ) ) { $sort_order = 'recent'; }
		$snapshot_types = array();
		foreach ( $all_snapshots as $snapshot_item ) {
			$type_name = sanitize_key( $snapshot_item['post_type'] ?? 'unknown' );
			if ( '' !== $type_name ) { $snapshot_types[ $type_name ] = sanitize_text_field( $snapshot_item['post_type'] ?? $type_name ); }
		}
		$filtered_snapshots = array_filter( $all_snapshots, function ( $snapshot_item ) use ( $search_term, $filter_post_type, $filter_status, $filter_freshness, $cutoff ) {
			$title = (string) ( $snapshot_item['title'] ?? '' );
			$url = (string) ( $snapshot_item['url'] ?? '' );
			if ( '' !== $search_term && false === stripos( $title . ' ' . $url, $search_term ) ) { return false; }
			$type = sanitize_key( $snapshot_item['post_type'] ?? 'unknown' );
			if ( '' !== $filter_post_type && $type !== $filter_post_type ) { return false; }
			$has_errors = (int) ( $snapshot_item['error_count'] ?? 0 ) > 0;
			if ( 'errors' === $filter_status && ! $has_errors ) { return false; }
			if ( 'clear' === $filter_status && $has_errors ) { return false; }
			$is_stale = empty( $snapshot_item['captured_at_ts'] ) || (int) $snapshot_item['captured_at_ts'] < $cutoff;
			if ( 'stale' === $filter_freshness && ! $is_stale ) { return false; }
			if ( 'recent' === $filter_freshness && $is_stale ) { return false; }
			return true;
		} );
		if ( 'oldest' === $sort_order ) {
			uasort( $filtered_snapshots, function ( $a, $b ) { return (int) ( $a['captured_at_ts'] ?? 0 ) <=> (int) ( $b['captured_at_ts'] ?? 0 ); } );
		} elseif ( 'errors' === $sort_order ) {
			uasort( $filtered_snapshots, function ( $a, $b ) { $errors = (int) ( $b['error_count'] ?? 0 ) <=> (int) ( $a['error_count'] ?? 0 ); return 0 !== $errors ? $errors : (int) ( $b['captured_at_ts'] ?? 0 ) <=> (int) ( $a['captured_at_ts'] ?? 0 ); } );
		}
		$filtered_total = count( $filtered_snapshots );
		$per_page = 20;
		$total_pages = max( 1, (int) ceil( $filtered_total / $per_page ) );
		$current_page = min( $current_page, $total_pages );
		$snapshot_offset = ( $current_page - 1 ) * $per_page;
		$display_snapshots = array_slice( $filtered_snapshots, $snapshot_offset, $per_page, true );
		?>
		<div class="wrap schema-frameworks-dashboard">
			<h1 class="sf-dashboard-title"><span class="dashicons dashicons-networking" aria-hidden="true"></span><?php echo esc_html__( 'Schema Frameworks Dashboard', 'schema-frameworks' ); ?></h1>
			<p><?php echo esc_html__( 'A private overview of module configuration, content mapping, and diagnostic snapshots. Dashboard diagnostics never modify public JSON-LD.', 'schema-frameworks' ); ?></p>
			<?php if ( isset( $_GET['diagnostics_refreshed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['diagnostics_refreshed'] ) ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Expired diagnostic snapshots were removed and the overview was refreshed.', 'schema-frameworks' ); ?></p></div>
			<?php endif; ?>
			<div class="sf-dashboard-cards">
				<?php $cards = array(
					array( __( 'Registered schema modules', 'schema-frameworks' ), count( $metadata ) ),
					array( __( 'Globally enabled modules', 'schema-frameworks' ), $enabled_modules ),
					array( __( 'Public post types', 'schema-frameworks' ), count( $post_types ) ),
					array( __( 'Confirmed post-type mappings', 'schema-frameworks' ), $confirmed ),
					array( __( 'Stored diagnostic snapshots', 'schema-frameworks' ), count( $snapshots ) ),
					array( __( 'Published public content diagnosed', 'schema-frameworks' ), $diagnosed_public_count . ' / ' . $total_published_public ),
					array( __( 'Diagnostic coverage', 'schema-frameworks' ), $coverage_percent . '%' ),
					array( __( 'Schema entities observed', 'schema-frameworks' ), $entity_count ),
					array( __( 'Relationship references observed', 'schema-frameworks' ), $relationship_count ),
					array( __( 'Snapshots older than 24 hours', 'schema-frameworks' ), $stale ),
					array( __( 'Recorded node errors', 'schema-frameworks' ), $issues ),
				); foreach ( $cards as $card ) : ?>
					<div class="sf-metric-card"><div class="sf-metric-label"><?php echo esc_html( $card[0] ); ?></div><div class="sf-metric-value"><?php echo esc_html( is_numeric( $card[1] ) ? number_format_i18n( (int) $card[1] ) : (string) $card[1] ); ?></div></div>
				<?php endforeach; ?>
			</div>
			<nav class="sf-dashboard-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Dashboard sections', 'schema-frameworks' ); ?>">
				<button type="button" class="sf-dashboard-tab is-active" id="sf-tab-overview" role="tab" aria-selected="true" aria-controls="sf-panel-overview" tabindex="0" data-panel="sf-panel-overview"><span class="dashicons dashicons-dashboard" aria-hidden="true"></span><?php echo esc_html__( 'Overview', 'schema-frameworks' ); ?></button>
				<button type="button" class="sf-dashboard-tab" id="sf-tab-coverage" role="tab" aria-selected="false" aria-controls="sf-panel-coverage" tabindex="-1" data-panel="sf-panel-coverage"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><?php echo esc_html__( 'Coverage & schema types', 'schema-frameworks' ); ?></button>
				<button type="button" class="sf-dashboard-tab" id="sf-tab-snapshots" role="tab" aria-selected="false" aria-controls="sf-panel-snapshots" tabindex="-1" data-panel="sf-panel-snapshots"><span class="dashicons dashicons-backup" aria-hidden="true"></span><?php echo esc_html__( 'Snapshots', 'schema-frameworks' ); ?></button>
				<button type="button" class="sf-dashboard-tab" id="sf-tab-tools" role="tab" aria-selected="false" aria-controls="sf-panel-tools" tabindex="-1" data-panel="sf-panel-tools"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span><?php echo esc_html__( 'Scan & maintenance', 'schema-frameworks' ); ?></button>
			</nav>
			<div class="sf-dashboard-panel is-active" id="sf-panel-overview" role="tabpanel" aria-labelledby="sf-tab-overview">
				<section class="sf-dashboard-section">
			<h2><?php echo esc_html__( 'Schema module inventory', 'schema-frameworks' ); ?></h2>
			<p><?php echo esc_html__( 'Registered modules are capabilities known to the plugin. Globally enabled means the module switch is on in settings; it does not guarantee output on every URL. A module may still require suitable content, a confirmed post-type mapping, per-content activation, or its own configuration.', 'schema-frameworks' ); ?></p>
			<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Module', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Schema.org type', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Global status', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Purpose', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Configuration', 'schema-frameworks' ); ?></th></tr></thead><tbody>
			<?php foreach ( $metadata as $module_id => $item ) : $module_enabled = (bool) $this->config->get( 'modules.' . $module_id, false ); ?>
			<tr><th scope="row"><strong><?php echo esc_html( $item['label'] ?? $module_id ); ?></strong><br /><code><?php echo esc_html( $module_id ); ?></code></th><td><?php echo esc_html( $item['schema_type'] ?? '—' ); ?></td><td><?php echo $module_enabled ? '<strong style="color:#008a20">' . esc_html__( 'Enabled', 'schema-frameworks' ) . '</strong>' : esc_html__( 'Disabled', 'schema-frameworks' ); ?></td><td><?php echo esc_html( $item['description'] ?? '' ); ?></td><td><?php echo ! empty( $item['admin_managed'] ) ? esc_html__( 'Module settings', 'schema-frameworks' ) : esc_html__( 'Managed automatically / internal dependency', 'schema-frameworks' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<h2><?php echo esc_html__( 'How to read these metrics', 'schema-frameworks' ); ?></h2>
			<ul>
			<li><strong><?php echo esc_html__( 'Registered schema modules', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'all module definitions registered by the plugin, including internal modules such as WebPage, Breadcrumb, Organization, and Organization Logo.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Globally enabled modules', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'registered modules whose global modules.* setting is enabled. Internal or automatically managed modules may not use a toggle like content modules do.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Public post types', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'WordPress content types currently marked public; this is not the number of mapped types.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Confirmed post-type mappings', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'saved mapping decisions explicitly confirmed by an administrator. Suggestions alone are not counted as confirmed.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Stored diagnostic snapshots', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'per-content diagnostic records captured during eligible administrator visits; this is not a full-site crawl.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Snapshots older than 24 hours', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'stored records whose last capture is more than 24 hours old or has no valid capture timestamp.', 'schema-frameworks' ); ?></li>
			<li><strong><?php echo esc_html__( 'Recorded node errors', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html__( 'the total node and graph errors saved in retained snapshots, not a claim that every page on the site has been scanned.', 'schema-frameworks' ); ?></li>
			</ul>
				</section>
			</div>
			<div class="sf-dashboard-panel" id="sf-panel-coverage" role="tabpanel" aria-labelledby="sf-tab-coverage" hidden>
				<section class="sf-dashboard-section">
			<h2><?php echo esc_html__( 'Diagnostic coverage by public post type', 'schema-frameworks' ); ?></h2>
			<p><?php echo esc_html__( 'Coverage compares retained snapshots with currently published items in public post types. It is an estimate based only on existing snapshots, not a scan of the site; items without snapshots have not been diagnosed by this dashboard.', 'schema-frameworks' ); ?></p>
			<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Public post type', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Published items', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Diagnosed items', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Coverage', 'schema-frameworks' ); ?></th></tr></thead><tbody>
			<?php foreach ( $post_type_coverage as $post_type_key => $coverage_row ) : $type_percent = $coverage_row['published'] > 0 ? min( 100, round( ( $coverage_row['diagnosed'] / $coverage_row['published'] ) * 100, 1 ) ) : 0; ?>
			<tr><th scope="row"><?php echo esc_html( $coverage_row['label'] ); ?> <code><?php echo esc_html( $post_type_key ); ?></code></th><td><?php echo esc_html( number_format_i18n( $coverage_row['published'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( min( $coverage_row['diagnosed'], $coverage_row['published'] ) ) ); ?></td><td><?php echo esc_html( $type_percent . '%' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<h2><?php echo esc_html__( 'Observed schema types', 'schema-frameworks' ); ?></h2>
			<?php if ( empty( $schema_type_counts ) ) : ?><p><?php echo esc_html__( 'No schema type data is available in the retained snapshots yet. Newly captured snapshots will populate this summary.', 'schema-frameworks' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Schema.org type', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Occurrences across retained content snapshots', 'schema-frameworks' ); ?></th></tr></thead><tbody>
			<?php foreach ( $schema_type_counts as $schema_type => $type_count ) : ?><tr><th scope="row"><code><?php echo esc_html( $schema_type ); ?></code></th><td><?php echo esc_html( number_format_i18n( $type_count ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php endif; ?>
				</section>
			</div>
			<div class="sf-dashboard-panel" id="sf-panel-snapshots" role="tabpanel" aria-labelledby="sf-tab-snapshots" hidden>
				<section class="sf-dashboard-section">
			<h2><?php echo esc_html__( 'Recent diagnostic snapshots', 'schema-frameworks' ); ?></h2>
			<p><?php echo esc_html__( 'Snapshots are recorded only when a logged-in administrator visits front-end content that triggers Schema Frameworks graph diagnostics. Snapshots expire after 30 days and are capped at 200 content records.', 'schema-frameworks' ); ?></p>
			<div class="sf-snapshot-export-toolbar">
				<p class="sf-snapshot-export-note"><?php echo esc_html__( 'Exports retained diagnostic summaries only; detailed node payloads are excluded.', 'schema-frameworks' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sf-snapshot-export-form">
					<input type="hidden" name="action" value="schema_frameworks_export_diagnostics" />
					<input type="hidden" name="sf_snapshot_search" value="<?php echo esc_attr( $search_term ); ?>" />
					<input type="hidden" name="sf_snapshot_type" value="<?php echo esc_attr( $filter_post_type ); ?>" />
					<input type="hidden" name="sf_snapshot_status" value="<?php echo esc_attr( $filter_status ); ?>" />
					<input type="hidden" name="sf_snapshot_freshness" value="<?php echo esc_attr( $filter_freshness ); ?>" />
					<input type="hidden" name="sf_snapshot_sort" value="<?php echo esc_attr( $sort_order ); ?>" />
					<?php wp_nonce_field( 'schema_frameworks_export_diagnostics', 'schema_frameworks_export_nonce' ); ?>
					<?php submit_button( __( 'Export snapshots as CSV', 'schema-frameworks' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<?php if ( empty( $all_snapshots ) ) : ?>
				<div class="notice notice-info inline"><p><?php echo esc_html__( 'No persistent snapshots are available yet. While logged in as an administrator, visit a front-end post or page that generates Schema Frameworks nodes, then return here.', 'schema-frameworks' ); ?></p></div>
			<?php else : ?>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="sf-snapshot-filters">
					<input type="hidden" name="page" value="schema-frameworks-dashboard" />
					<input type="hidden" name="sf_dashboard_tab" value="snapshots" />
					<label><span><?php echo esc_html__( 'Search title or URL', 'schema-frameworks' ); ?></span><input type="search" name="sf_snapshot_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php echo esc_attr__( 'Search snapshots…', 'schema-frameworks' ); ?>" /></label>
					<label><span><?php echo esc_html__( 'Post type', 'schema-frameworks' ); ?></span><select name="sf_snapshot_type"><option value=""><?php echo esc_html__( 'All post types', 'schema-frameworks' ); ?></option><?php foreach ( $snapshot_types as $type_key => $type_label ) : ?><option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $filter_post_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php echo esc_html__( 'Diagnostic status', 'schema-frameworks' ); ?></span><select name="sf_snapshot_status"><option value="all" <?php selected( $filter_status, 'all' ); ?>><?php echo esc_html__( 'All statuses', 'schema-frameworks' ); ?></option><option value="errors" <?php selected( $filter_status, 'errors' ); ?>><?php echo esc_html__( 'Has errors', 'schema-frameworks' ); ?></option><option value="clear" <?php selected( $filter_status, 'clear' ); ?>><?php echo esc_html__( 'No recorded errors', 'schema-frameworks' ); ?></option></select></label>
					<label><span><?php echo esc_html__( 'Freshness', 'schema-frameworks' ); ?></span><select name="sf_snapshot_freshness"><option value="all" <?php selected( $filter_freshness, 'all' ); ?>><?php echo esc_html__( 'All snapshots', 'schema-frameworks' ); ?></option><option value="recent" <?php selected( $filter_freshness, 'recent' ); ?>><?php echo esc_html__( 'Recent (24 hours)', 'schema-frameworks' ); ?></option><option value="stale" <?php selected( $filter_freshness, 'stale' ); ?>><?php echo esc_html__( 'Stale', 'schema-frameworks' ); ?></option></select></label>
					<label><span><?php echo esc_html__( 'Sort by', 'schema-frameworks' ); ?></span><select name="sf_snapshot_sort"><option value="recent" <?php selected( $sort_order, 'recent' ); ?>><?php echo esc_html__( 'Newest first', 'schema-frameworks' ); ?></option><option value="oldest" <?php selected( $sort_order, 'oldest' ); ?>><?php echo esc_html__( 'Oldest first', 'schema-frameworks' ); ?></option><option value="errors" <?php selected( $sort_order, 'errors' ); ?>><?php echo esc_html__( 'Most errors', 'schema-frameworks' ); ?></option></select></label>
					<div class="sf-snapshot-filter-actions"><?php submit_button( __( 'Apply filters', 'schema-frameworks' ), 'primary', 'submit', false ); ?> <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'schema-frameworks-dashboard', 'sf_dashboard_tab' => 'snapshots' ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'Reset', 'schema-frameworks' ); ?></a></div>
				</form>
				<p class="sf-snapshot-result-count"><?php echo esc_html( sprintf( __( 'Showing %1$d–%2$d of %3$d matching snapshots (%4$d retained total).', 'schema-frameworks' ), $filtered_total ? $snapshot_offset + 1 : 0, min( $snapshot_offset + $per_page, $filtered_total ), $filtered_total, count( $all_snapshots ) ) ); ?></p>
				<?php if ( empty( $display_snapshots ) ) : ?><div class="notice notice-info inline"><p><?php echo esc_html__( 'No snapshots match these filters. Try adjusting or resetting your filters.', 'schema-frameworks' ); ?></p></div><?php else : ?>
				<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Content', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Schema nodes', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Errors', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Warnings', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Last captured', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Freshness', 'schema-frameworks' ); ?></th></tr></thead><tbody>
				<?php foreach ( $display_snapshots as $snapshot ) : $is_stale = empty( $snapshot['captured_at_ts'] ) || (int) $snapshot['captured_at_ts'] < $cutoff; $snapshot_types = isset( $snapshot['schema_types'] ) && is_array( $snapshot['schema_types'] ) ? $snapshot['schema_types'] : array(); $snapshot_errors = isset( $snapshot['graph_errors'] ) && is_array( $snapshot['graph_errors'] ) ? $snapshot['graph_errors'] : array(); $snapshot_nodes = isset( $snapshot['nodes'] ) && is_array( $snapshot['nodes'] ) ? $snapshot['nodes'] : array(); ?>
					<tr><td><?php if ( ! empty( $snapshot['url'] ) ) : ?><a href="<?php echo esc_url( $snapshot['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $snapshot['title'] ?? $snapshot['url'] ); ?></a><?php else : echo esc_html( $snapshot['title'] ?? __( 'Unknown content', 'schema-frameworks' ) ); endif; ?><br /><code><?php echo esc_html( $snapshot['post_type'] ?? 'unknown' ); ?></code>
					<details class="sf-snapshot-details"><summary><?php echo esc_html__( 'Inspect snapshot', 'schema-frameworks' ); ?></summary><div class="sf-snapshot-detail-body">
						<p><strong><?php echo esc_html__( 'Schema types', 'schema-frameworks' ); ?>:</strong> <?php echo ! empty( $snapshot_types ) ? esc_html( implode( ', ', array_map( 'sanitize_text_field', $snapshot_types ) ) ) : esc_html__( 'Not recorded in this snapshot', 'schema-frameworks' ); ?></p>
						<p><strong><?php echo esc_html__( 'Entity IDs observed', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html( (string) ( $snapshot['entity_count'] ?? $snapshot['node_count'] ?? 0 ) ); ?> · <strong><?php echo esc_html__( 'Relationship references', 'schema-frameworks' ); ?>:</strong> <?php echo esc_html( (string) ( $snapshot['relationship_count'] ?? 0 ) ); ?></p>
						<?php if ( ! empty( $snapshot_errors ) ) : ?><p><strong><?php echo esc_html__( 'Recorded graph errors', 'schema-frameworks' ); ?>:</strong></p><ul><?php foreach ( array_slice( $snapshot_errors, 0, 20 ) as $snapshot_error ) : ?><li><?php echo esc_html( $snapshot_error ); ?></li><?php endforeach; ?></ul><?php elseif ( empty( $snapshot['error_count'] ) ) : ?><p><?php echo esc_html__( 'No graph errors were recorded in this snapshot.', 'schema-frameworks' ); ?></p><?php endif; ?>
						<?php if ( ! empty( $snapshot_nodes ) ) : ?><details><summary><?php echo esc_html__( 'Node summary', 'schema-frameworks' ); ?></summary><ul><?php foreach ( array_slice( $snapshot_nodes, 0, 30 ) as $snapshot_node ) : if ( ! is_array( $snapshot_node ) ) { continue; } ?><li><code><?php echo esc_html( $snapshot_node['type'] ?? $snapshot_node['@type'] ?? 'Unknown' ); ?></code><?php if ( ! empty( $snapshot_node['@id'] ) ) : ?> — <code><?php echo esc_html( $snapshot_node['@id'] ); ?></code><?php endif; ?><?php if ( ! empty( $snapshot_node['_debug_note'] ) ) : ?><br /><?php echo esc_html( $snapshot_node['_debug_note'] ); ?><?php endif; ?></li><?php endforeach; ?></ul></details><?php endif; ?>
					</div></details></td><td><?php echo esc_html( (string) ( $snapshot['node_count'] ?? 0 ) ); ?></td><td><span class="sf-count-pill <?php echo ! empty( $snapshot['error_count'] ) ? 'has-errors' : 'is-clear'; ?>"><?php echo esc_html( (string) ( $snapshot['error_count'] ?? 0 ) ); ?></span></td><td><?php echo esc_html( (string) ( $snapshot['warning_count'] ?? 0 ) ); ?></td><td><?php echo esc_html( $snapshot['captured_at'] ?? '—' ); ?></td><td><?php echo $is_stale ? '<span class="sf-freshness-pill is-stale">' . esc_html__( 'Stale', 'schema-frameworks' ) . '</span>' : '<span class="sf-freshness-pill is-recent">' . esc_html__( 'Recent', 'schema-frameworks' ) . '</span>'; ?></td></tr>
				<?php endforeach; ?></tbody></table>
				<?php if ( $total_pages > 1 ) : ?><nav class="sf-snapshot-pagination" aria-label="<?php echo esc_attr__( 'Snapshot pages', 'schema-frameworks' ); ?>"><span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'schema-frameworks' ), $current_page, $total_pages ) ); ?></span><div><?php if ( $current_page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'schema-frameworks-dashboard', 'sf_dashboard_tab' => 'snapshots', 'sf_snapshot_search' => $search_term, 'sf_snapshot_type' => $filter_post_type, 'sf_snapshot_status' => $filter_status, 'sf_snapshot_freshness' => $filter_freshness, 'sf_snapshot_sort' => $sort_order, 'sf_snapshot_page' => $current_page - 1 ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'Previous', 'schema-frameworks' ); ?></a><?php endif; ?> <?php if ( $current_page < $total_pages ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'schema-frameworks-dashboard', 'sf_dashboard_tab' => 'snapshots', 'sf_snapshot_search' => $search_term, 'sf_snapshot_type' => $filter_post_type, 'sf_snapshot_status' => $filter_status, 'sf_snapshot_freshness' => $filter_freshness, 'sf_snapshot_sort' => $sort_order, 'sf_snapshot_page' => $current_page + 1 ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'Next', 'schema-frameworks' ); ?></a><?php endif; ?></div></nav><?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
				</section>
			</div>
			<div class="sf-dashboard-panel" id="sf-panel-tools" role="tabpanel" aria-labelledby="sf-tab-tools" hidden>
				<section class="sf-dashboard-section">
			<h2><?php echo esc_html__( 'Bounded batch scan', 'schema-frameworks' ); ?></h2>
			<p><?php echo esc_html__( 'Explicitly scan published public content in small batches. The browser visits same-site content URLs while you are logged in, allowing eligible front-end diagnostics to record snapshots. This can increase server load; start with a small batch and keep this Dashboard open while the scan runs.', 'schema-frameworks' ); ?></p>
			<div id="sf-batch-scan" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'schema_frameworks_batch_scan' ) ); ?>" data-running="<?php echo esc_attr( (string) ( get_option( 'schema_frameworks_batch_scan_state', array() )['status'] ?? 'idle' ) ); ?>" class="sf-batch-scan-card">
				<label for="sf-scan-batch-size"><strong><?php echo esc_html__( 'Items per batch', 'schema-frameworks' ); ?></strong></label>
				<select id="sf-scan-batch-size"><option value="3">3</option><option value="5" selected>5</option><option value="10">10</option></select>
				<button type="button" class="button button-primary" id="sf-scan-start"><?php echo esc_html__( 'Start new scan', 'schema-frameworks' ); ?></button>
				<button type="button" class="button" id="sf-scan-resume"><?php echo esc_html__( 'Resume scan', 'schema-frameworks' ); ?></button>
				<button type="button" class="button" id="sf-scan-pause"><?php echo esc_html__( 'Pause scan', 'schema-frameworks' ); ?></button>
				<p id="sf-scan-status" class="sf-scan-status is-idle" role="status" aria-live="polite"><?php echo esc_html__( 'No batch scan has been started.', 'schema-frameworks' ); ?></p>
				<progress id="sf-scan-progress" max="100" value="0" class="sf-scan-progress" aria-label="<?php echo esc_attr__( 'Batch scan progress', 'schema-frameworks' ); ?>"></progress>
				<dl class="sf-scan-counters" aria-live="polite"><div><dt><?php echo esc_html__( 'Processed', 'schema-frameworks' ); ?></dt><dd id="sf-scan-processed">0</dd></div><div><dt><?php echo esc_html__( 'Remaining', 'schema-frameworks' ); ?></dt><dd id="sf-scan-remaining">—</dd></div><div><dt><?php echo esc_html__( 'Failed loads', 'schema-frameworks' ); ?></dt><dd id="sf-scan-failed">0</dd></div><div><dt><?php echo esc_html__( 'Last update', 'schema-frameworks' ); ?></dt><dd id="sf-scan-updated">—</dd></div></dl>
			</div>
			<h2><?php echo esc_html__( 'Manual diagnostics maintenance', 'schema-frameworks' ); ?></h2>
			<p><?php echo esc_html__( 'Remove expired snapshots and recalculate the overview. This does not crawl the site or trigger front-end schema generation.', 'schema-frameworks' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="schema_frameworks_refresh_diagnostics" />
				<?php wp_nonce_field( 'schema_frameworks_refresh_diagnostics', 'schema_frameworks_diagnostics_nonce' ); ?>
				<?php submit_button( __( 'Purge expired snapshots and refresh', 'schema-frameworks' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php if ( is_array( $latest ) && ! empty( $latest['captured_at'] ) ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Latest in-memory graph validation: %s', 'schema-frameworks' ), $latest['captured_at'] ) ); ?> — <a href="<?php echo esc_url( admin_url( 'admin.php?page=schema-frameworks-validation' ) ); ?>"><?php echo esc_html__( 'Open Graph Validation', 'schema-frameworks' ); ?></a></p><?php endif; ?>
				</section>
			</div>
		</div>
		<?php
	}

	private function validate_batch_scan_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to run diagnostic scans.', 'schema-frameworks' ) ), 403 );
		}
		check_ajax_referer( 'schema_frameworks_batch_scan', 'nonce' );
	}

	public function ajax_scan_start() {
		$this->validate_batch_scan_request();
		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 5;
		$batch_size = max( 1, min( 10, $batch_size ) );
		$post_types = array_values( array_map( 'sanitize_key', array_keys( get_post_types( array( 'public' => true ), 'names' ) ) ) );
		$total = 0;
		foreach ( $post_types as $post_type ) {
			$counts = wp_count_posts( $post_type );
			if ( is_object( $counts ) && isset( $counts->publish ) ) {
				$total += (int) $counts->publish;
			}
		}
		$state = array( 'status' => 0 === $total ? 'completed' : 'running', 'post_types' => $post_types, 'total' => $total, 'cursor' => 0, 'processed' => 0, 'failed' => 0, 'batch_size' => $batch_size, 'started_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) );
		update_option( 'schema_frameworks_batch_scan_state', $state, false );
		wp_send_json_success( $this->public_scan_state( $state ) );
	}

	public function ajax_scan_next() {
		$this->validate_batch_scan_request();
		$state = get_option( 'schema_frameworks_batch_scan_state', array() );
		if ( ! is_array( $state ) || ( ! isset( $state['post_types'] ) && ! isset( $state['items'] ) ) ) { wp_send_json_error( array( 'message' => __( 'No scan is available. Start a new scan first.', 'schema-frameworks' ) ), 400 ); }
		if ( 'running' !== ( $state['status'] ?? '' ) ) { wp_send_json_success( array( 'state' => $this->public_scan_state( $state ), 'items' => array() ) ); }
		$cursor = max( 0, (int) ( $state['cursor'] ?? 0 ) );
		$batch_size = max( 1, min( 10, (int) ( $state['batch_size'] ?? 5 ) ) );
		if ( isset( $state['post_types'] ) && is_array( $state['post_types'] ) ) {
			$batch_ids = get_posts( array( 'post_type' => array_values( array_map( 'sanitize_key', $state['post_types'] ) ), 'post_status' => 'publish', 'posts_per_page' => $batch_size, 'offset' => $cursor, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'suppress_filters' => true ) );
			$batch_ids = array_values( array_map( 'absint', $batch_ids ) );
		} else {
			$batch_ids = array_slice( (array) $state['items'], $cursor, $batch_size );
		}
		if ( empty( $batch_ids ) ) {
			$state['status'] = 'completed';
			$state['cursor'] = (int) ( $state['total'] ?? $cursor );
			$state['updated_at'] = current_time( 'mysql' );
			update_option( 'schema_frameworks_batch_scan_state', $state, false );
			wp_send_json_success( array( 'state' => $this->public_scan_state( $state ), 'items' => array() ) );
		}
		$items = array();
		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		foreach ( $batch_ids as $post_id ) {
			$post = get_post( (int) $post_id );
			$url = $post && 'publish' === $post->post_status ? get_permalink( $post ) : '';
			if ( ! $url || wp_parse_url( $url, PHP_URL_HOST ) !== $home_host ) { $items[] = array( 'id' => (int) $post_id, 'url' => '', 'title' => '' ); continue; }
			$items[] = array( 'id' => (int) $post_id, 'url' => esc_url_raw( $url ), 'title' => sanitize_text_field( get_the_title( $post ) ) );
		}
		wp_send_json_success( array( 'state' => $this->public_scan_state( $state ), 'items' => $items ) );
	}

	public function ajax_scan_progress() {
		$this->validate_batch_scan_request();
		$state = get_option( 'schema_frameworks_batch_scan_state', array() );
		if ( ! is_array( $state ) || ! in_array( ( $state['status'] ?? '' ), array( 'running', 'paused' ), true ) ) { wp_send_json_error( array( 'message' => __( 'The scan is not running.', 'schema-frameworks' ) ), 409 ); }
		$count = isset( $_POST['attempted'] ) ? absint( $_POST['attempted'] ) : 0;
		$failed = isset( $_POST['failed'] ) ? absint( $_POST['failed'] ) : 0;
		$remaining = max( 0, (int) $state['total'] - (int) $state['cursor'] );
		$count = min( $count, min( (int) $state['batch_size'], $remaining ) );
		$state['cursor'] = (int) $state['cursor'] + $count;
		$state['processed'] = (int) $state['processed'] + $count;
		$state['failed'] = (int) $state['failed'] + min( $failed, $count );
		$state['updated_at'] = current_time( 'mysql' );
		if ( (int) $state['cursor'] >= (int) $state['total'] ) { $state['status'] = 'completed'; }
		update_option( 'schema_frameworks_batch_scan_state', $state, false );
		wp_send_json_success( $this->public_scan_state( $state ) );
	}

	public function ajax_scan_control() {
		$this->validate_batch_scan_request();
		$state = get_option( 'schema_frameworks_batch_scan_state', array() );
		$command = isset( $_POST['command'] ) ? sanitize_key( wp_unslash( $_POST['command'] ) ) : '';
		if ( ! is_array( $state ) || ( ! isset( $state['items'] ) && ! isset( $state['post_types'] ) ) || ! in_array( $command, array( 'pause', 'resume' ), true ) ) { wp_send_json_error( array( 'message' => __( 'No valid scan is available to control.', 'schema-frameworks' ) ), 400 ); }
		if ( 'completed' !== ( $state['status'] ?? '' ) ) { $state['status'] = 'pause' === $command ? 'paused' : 'running'; }
		$state['updated_at'] = current_time( 'mysql' );
		update_option( 'schema_frameworks_batch_scan_state', $state, false );
		wp_send_json_success( $this->public_scan_state( $state ) );
	}

	private function public_scan_state( $state ) {
		return array( 'status' => sanitize_key( $state['status'] ?? 'idle' ), 'total' => (int) ( $state['total'] ?? 0 ), 'processed' => (int) ( $state['processed'] ?? 0 ), 'failed' => (int) ( $state['failed'] ?? 0 ), 'cursor' => (int) ( $state['cursor'] ?? 0 ), 'batch_size' => (int) ( $state['batch_size'] ?? 5 ), 'updated_at' => sanitize_text_field( $state['updated_at'] ?? '' ) );
	}

	public function handle_refresh_diagnostics() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage diagnostics.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'schema_frameworks_refresh_diagnostics', 'schema_frameworks_diagnostics_nonce' );
		$this->get_diagnostic_snapshots( true );
		wp_safe_redirect( add_query_arg( array( 'page' => 'schema-frameworks-dashboard', 'diagnostics_refreshed' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_export_diagnostics() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export diagnostics.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'schema_frameworks_export_diagnostics', 'schema_frameworks_export_nonce' );
		$snapshots = $this->get_diagnostic_snapshots();
		$search_term = isset( $_POST['sf_snapshot_search'] ) ? sanitize_text_field( wp_unslash( $_POST['sf_snapshot_search'] ) ) : '';
		$filter_post_type = isset( $_POST['sf_snapshot_type'] ) ? sanitize_key( wp_unslash( $_POST['sf_snapshot_type'] ) ) : '';
		$filter_status = isset( $_POST['sf_snapshot_status'] ) ? sanitize_key( wp_unslash( $_POST['sf_snapshot_status'] ) ) : 'all';
		$filter_freshness = isset( $_POST['sf_snapshot_freshness'] ) ? sanitize_key( wp_unslash( $_POST['sf_snapshot_freshness'] ) ) : 'all';
		$sort_order = isset( $_POST['sf_snapshot_sort'] ) ? sanitize_key( wp_unslash( $_POST['sf_snapshot_sort'] ) ) : 'recent';
		if ( ! in_array( $filter_status, array( 'all', 'errors', 'clear' ), true ) ) { $filter_status = 'all'; }
		if ( ! in_array( $filter_freshness, array( 'all', 'recent', 'stale' ), true ) ) { $filter_freshness = 'all'; }
		if ( ! in_array( $sort_order, array( 'recent', 'oldest', 'errors' ), true ) ) { $sort_order = 'recent'; }
		$cutoff = time() - DAY_IN_SECONDS;
		$snapshots = array_filter( $snapshots, function ( $snapshot ) use ( $search_term, $filter_post_type, $filter_status, $filter_freshness, $cutoff ) {
			$title = (string) ( $snapshot['title'] ?? '' );
			$url = (string) ( $snapshot['url'] ?? '' );
			if ( '' !== $search_term && false === stripos( $title . ' ' . $url, $search_term ) ) { return false; }
			if ( '' !== $filter_post_type && sanitize_key( $snapshot['post_type'] ?? 'unknown' ) !== $filter_post_type ) { return false; }
			$has_errors = (int) ( $snapshot['error_count'] ?? 0 ) > 0;
			if ( 'errors' === $filter_status && ! $has_errors ) { return false; }
			if ( 'clear' === $filter_status && $has_errors ) { return false; }
			$is_stale = empty( $snapshot['captured_at_ts'] ) || (int) $snapshot['captured_at_ts'] < $cutoff;
			if ( 'stale' === $filter_freshness && ! $is_stale ) { return false; }
			if ( 'recent' === $filter_freshness && $is_stale ) { return false; }
			return true;
		} );
		if ( 'oldest' === $sort_order ) {
			uasort( $snapshots, function ( $a, $b ) { return (int) ( $a['captured_at_ts'] ?? 0 ) <=> (int) ( $b['captured_at_ts'] ?? 0 ); } );
		} elseif ( 'errors' === $sort_order ) {
			uasort( $snapshots, function ( $a, $b ) { $errors = (int) ( $b['error_count'] ?? 0 ) <=> (int) ( $a['error_count'] ?? 0 ); return 0 !== $errors ? $errors : (int) ( $b['captured_at_ts'] ?? 0 ) <=> (int) ( $a['captured_at_ts'] ?? 0 ); } );
		}
		if ( headers_sent() ) {
			wp_die( esc_html__( 'The CSV export could not start because output has already been sent.', 'schema-frameworks' ) );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=schema-frameworks-diagnostics-' . gmdate( 'Y-m-d-His' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to create the CSV export.', 'schema-frameworks' ) );
		}
		fputcsv( $output, array( 'Title', 'URL', 'Post Type', 'Captured At', 'Schema Nodes', 'Entities', 'Relationship References', 'Schema Types', 'Errors', 'Warnings', 'Recorded Graph Errors' ) );
		foreach ( $snapshots as $snapshot ) {
			$values = array(
				$snapshot['title'] ?? '',
				$snapshot['url'] ?? '',
				$snapshot['post_type'] ?? 'unknown',
				$snapshot['captured_at'] ?? '',
				(int) ( $snapshot['node_count'] ?? 0 ),
				(int) ( $snapshot['entity_count'] ?? $snapshot['node_count'] ?? 0 ),
				(int) ( $snapshot['relationship_count'] ?? 0 ),
				isset( $snapshot['schema_types'] ) && is_array( $snapshot['schema_types'] ) ? implode( '; ', array_map( 'sanitize_text_field', $snapshot['schema_types'] ) ) : '',
				(int) ( $snapshot['error_count'] ?? 0 ),
				(int) ( $snapshot['warning_count'] ?? 0 ),
				isset( $snapshot['graph_errors'] ) && is_array( $snapshot['graph_errors'] ) ? implode( '; ', array_map( 'sanitize_text_field', $snapshot['graph_errors'] ) ) : '',
			);
			foreach ( $values as $index => $value ) {
				if ( is_string( $value ) && preg_match( '/^[\s]*[=+@\-]/', $value ) ) {
					$values[ $index ] = "'" . $value;
				}
			}
			fputcsv( $output, $values );
		}
		fclose( $output );
		exit;
	}

	private function get_diagnostic_snapshots( $purge_expired = false ) {
		$snapshots = get_option( 'schema_frameworks_diagnostic_snapshots', array() );
		$snapshots = is_array( $snapshots ) ? $snapshots : array();
		$cutoff = time() - ( 30 * DAY_IN_SECONDS );
		$changed = false;
		foreach ( $snapshots as $key => $snapshot ) {
			if ( ! is_array( $snapshot ) || empty( $snapshot['captured_at_ts'] ) || (int) $snapshot['captured_at_ts'] < $cutoff ) {
				unset( $snapshots[ $key ] );
				$changed = true;
			}
		}
		if ( $purge_expired || $changed ) {
			update_option( 'schema_frameworks_diagnostic_snapshots', $snapshots, false );
		}
		 uasort( $snapshots, function ( $a, $b ) { return (int) ( $b['captured_at_ts'] ?? 0 ) <=> (int) ( $a['captured_at_ts'] ?? 0 ); } );
		return $snapshots;
	}

	private function store_diagnostic_snapshot( $report ) {
		if ( ! is_array( $report ) || empty( $report['nodes'] ) ) { return; }
		$post_id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
		$post = $post_id ? get_post( $post_id ) : null;
		$url = isset( $report['page_url'] ) ? esc_url_raw( $report['page_url'] ) : '';
		$key = $post_id ? 'post_' . $post_id : 'url_' . md5( $url );
		$nodes = array(); $error_count = 0; $warning_count = 0; $schema_types = array(); $entity_ids = array(); $relationship_edges = array();
		foreach ( $report['nodes'] as $node ) {
			$errors = isset( $node['errors'] ) && is_array( $node['errors'] ) ? array_slice( $node['errors'], 0, 20 ) : array();
			$warnings = isset( $node['warnings'] ) && is_array( $node['warnings'] ) ? array_slice( $node['warnings'], 0, 20 ) : array();
			$error_count += count( $errors ); $warning_count += count( $warnings );
			$type = sanitize_text_field( $node['type'] ?? 'Unknown' );
			if ( '' !== $type ) { $schema_types[ $type ] = true; }
			$node_id = esc_url_raw( $node['id'] ?? '' );
			if ( '' !== $node_id ) { $entity_ids[ $node_id ] = true; }
			if ( ! empty( $node['properties'] ) && is_array( $node['properties'] ) ) { $this->collect_debug_edges( $node['properties'], $node_id, $relationship_edges ); }
			$nodes[] = array( 'module' => sanitize_key( $node['module'] ?? '' ), 'type' => $type, 'id' => $node_id, 'errors' => array_map( 'sanitize_text_field', $errors ), 'warnings' => array_map( 'sanitize_text_field', $warnings ) );
		}
		$graph_errors = isset( $report['graph_errors'] ) && is_array( $report['graph_errors'] ) ? array_slice( $report['graph_errors'], 0, 20 ) : array();
		$error_count += count( $graph_errors );
		$snapshots = $this->get_diagnostic_snapshots();
		$snapshots[ $key ] = array( 'post_id' => $post_id, 'post_type' => $post && isset( $post->post_type ) ? sanitize_key( $post->post_type ) : 'url', 'title' => $post ? sanitize_text_field( get_the_title( $post ) ) : $url, 'url' => $url, 'captured_at' => current_time( 'mysql' ), 'captured_at_ts' => time(), 'node_count' => count( $nodes ), 'entity_count' => count( $entity_ids ), 'relationship_count' => count( $relationship_edges ), 'schema_types' => array_keys( $schema_types ), 'error_count' => $error_count, 'warning_count' => $warning_count, 'nodes' => array_slice( $nodes, 0, 100 ), 'graph_errors' => array_map( 'sanitize_text_field', $graph_errors ) );
		 uasort( $snapshots, function ( $a, $b ) { return (int) ( $b['captured_at_ts'] ?? 0 ) <=> (int) ( $a['captured_at_ts'] ?? 0 ); } );
		$snapshots = array_slice( $snapshots, 0, 200, true );
		update_option( 'schema_frameworks_diagnostic_snapshots', $snapshots, false );
	}

	public function render_page() {

		$config = $this->config;

		$view = SCHEMA_FRAMEWORKS_PATH
			. 'includes/admin/views/settings-general.php';

		if ( file_exists( $view ) ) {
			require $view;
		}
	}

	public function render_modules_page() {

		$config = $this->config;
		$registry = $this->registry;

		$view = SCHEMA_FRAMEWORKS_PATH
			. 'includes/admin/views/settings-modules.php';

		if ( file_exists( $view ) ) {
			require $view;
		}
	}

	public function render_organization_page() {

		$config = $this->config;

		$view = SCHEMA_FRAMEWORKS_PATH
			. 'includes/admin/views/settings-organization.php';

		if ( file_exists( $view ) ) {
			require $view;
		}
	}

	public function capture_graph_validation( $validation, $schema, $module_id, $page, $context = null ) {

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! is_array( $validation ) || ! is_array( $schema ) ) {
			return;
		}

		$page_url = function_exists( 'get_permalink' ) ? get_permalink() : '';
		if ( ! $page_url ) {
			$page_url = home_url( add_query_arg( array(), isset( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '' ) );
		}

		$report = get_transient( 'schema_frameworks_graph_validation_latest' );
		if ( ! is_array( $report ) || empty( $report['page_url'] ) || $report['page_url'] !== $page_url ) {
			$report = array(
				'page_url'    => $page_url,
				'captured_at' => current_time( 'mysql' ),
				'nodes'       => array(),
			);
		}

		$module_key = sanitize_key( (string) $module_id );
		if ( '' === $module_key ) {
			$module_key = 'node-' . count( $report['nodes'] );
		}

		$errors   = isset( $validation['errors'] ) && is_array( $validation['errors'] ) ? array_values( array_map( 'sanitize_text_field', $validation['errors'] ) ) : array();
		$warnings = isset( $validation['warnings'] ) && is_array( $validation['warnings'] ) ? array_values( array_map( 'sanitize_text_field', $validation['warnings'] ) ) : array();

		$report['captured_at'] = current_time( 'mysql' );
		$report['nodes'][ $module_key ] = array(
			'module'   => $module_key,
			'type'     => isset( $schema['@type'] ) ? sanitize_text_field( (string) $schema['@type'] ) : 'Unknown',
			'id'       => isset( $schema['@id'] ) ? esc_url_raw( (string) $schema['@id'] ) : '',
			'errors'   => $errors,
			'warnings' => $warnings,
			'properties' => $this->debug_safe_schema( $schema ),
		);

		// Bound transient size if a site has many schema modules.
		$report['nodes'] = array_slice( $report['nodes'], -100, 100, true );
		$report['graph_errors'] = array();
		$ids = array();
		foreach ( $report['nodes'] as $captured_node ) {
			if ( empty( $captured_node['id'] ) ) {
				continue;
			}
			$ids[ $captured_node['id'] ][] = $captured_node['module'];
		}
		foreach ( $ids as $entity_id => $modules ) {
			if ( count( $modules ) > 1 ) {
				$report['graph_errors'][] = sprintf( 'Duplicate @id %1$s observed for modules: %2$s.', $entity_id, implode( ', ', $modules ) );
			}
		}
		set_transient( 'schema_frameworks_graph_validation_latest', $report, HOUR_IN_SECONDS );
		$this->store_diagnostic_snapshot( $report );
	}

	public function render_graph_validation_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view graph diagnostics.', 'schema-frameworks' ) );
		}

		$report = get_transient( 'schema_frameworks_graph_validation_latest' );
		$nodes  = is_array( $report ) && ! empty( $report['nodes'] ) ? $report['nodes'] : array();
		$error_count = 0;
		$warning_count = 0;
		$graph_errors = is_array( $report ) && ! empty( $report['graph_errors'] ) ? $report['graph_errors'] : array();
		foreach ( $nodes as $node ) {
			$error_count += count( isset( $node['errors'] ) ? $node['errors'] : array() );
			$warning_count += count( isset( $node['warnings'] ) ? $node['warnings'] : array() );
		}
		$error_count += count( $graph_errors );
		?>
		<div class="wrap schema-frameworks-admin-page schema-frameworks-validation">
			<h1><?php echo esc_html__( 'Graph Validation', 'schema-frameworks' ); ?></h1>
			<p><?php echo esc_html__( 'Private diagnostics captured when a logged-in administrator views a page that generates Schema Frameworks nodes. This report does not change or suppress public JSON-LD.', 'schema-frameworks' ); ?></p>
			<?php if ( empty( $nodes ) ) : ?>
				<div class="notice notice-info"><p><?php echo esc_html__( 'No runtime diagnostics have been captured yet. While logged in as an administrator, visit a front-end page where Schema Frameworks generates schema, then return here and refresh this screen.', 'schema-frameworks' ); ?></p></div>
			<?php else : ?>
				<p><strong><?php echo esc_html__( 'Page:', 'schema-frameworks' ); ?></strong> <a href="<?php echo esc_url( $report['page_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $report['page_url'] ); ?></a><br />
				<strong><?php echo esc_html__( 'Last captured:', 'schema-frameworks' ); ?></strong> <?php echo esc_html( $report['captured_at'] ); ?></p>
				<div class="notice <?php echo $error_count ? 'notice-error' : 'notice-success'; ?>"><p><strong><?php echo esc_html( sprintf( __( '%d error(s)', 'schema-frameworks' ), $error_count ) ); ?></strong> &nbsp; <strong><?php echo esc_html( sprintf( __( '%d warning(s)', 'schema-frameworks' ), $warning_count ) ); ?></strong></p></div>
				<?php if ( ! empty( $graph_errors ) ) : ?><h2><?php echo esc_html__( 'Graph-level errors', 'schema-frameworks' ); ?></h2><ul class="ul-disc"><?php foreach ( $graph_errors as $message ) : ?><li><?php echo esc_html( $message ); ?></li><?php endforeach; ?></ul><?php endif; ?>
				<table class="widefat striped">
					<thead><tr><th><?php echo esc_html__( 'Module', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Schema type', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Entity @id', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Diagnostics', 'schema-frameworks' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $nodes as $node ) : ?>
						<tr>
							<td><code><?php echo esc_html( $node['module'] ); ?></code></td>
							<td><?php echo esc_html( $node['type'] ); ?></td>
							<td><?php echo $node['id'] ? '<code>' . esc_html( $node['id'] ) . '</code>' : '<em>' . esc_html__( 'No @id', 'schema-frameworks' ) . '</em>'; ?></td>
							<td>
								<?php if ( empty( $node['errors'] ) && empty( $node['warnings'] ) ) : ?>
									<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php echo esc_html__( 'No node-level issues detected.', 'schema-frameworks' ); ?>
								<?php else : ?>
									<?php foreach ( $node['errors'] as $message ) : ?><p><strong><?php echo esc_html__( 'Error:', 'schema-frameworks' ); ?></strong> <?php echo esc_html( $message ); ?></p><?php endforeach; ?>
									<?php foreach ( $node['warnings'] as $message ) : ?><p><strong><?php echo esc_html__( 'Warning:', 'schema-frameworks' ); ?></strong> <?php echo esc_html( $message ); ?></p><?php endforeach; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php echo esc_html__( 'This initial report displays node-level validation results. It is not a full Schema.org vocabulary or search-engine rich-result eligibility checker.', 'schema-frameworks' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_developer_support_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view developer support information.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap schema-frameworks-admin-page schema-frameworks-support">
			<h1><?php echo esc_html__( 'Developer Support', 'schema-frameworks' ); ?></h1>
			<p><?php echo esc_html__( 'Contact the developer or prepare a useful, privacy-conscious report when you encounter a reproducible issue.', 'schema-frameworks' ); ?></p>
			<div class="card" style="max-width:900px;padding:20px;">
				<h2><?php echo esc_html__( 'Developer contact', 'schema-frameworks' ); ?></h2>
				<p><strong><?php echo esc_html__( 'Developer:', 'schema-frameworks' ); ?></strong> Naushad A.</p>
				<p><strong><?php echo esc_html__( 'Email:', 'schema-frameworks' ); ?></strong> <a href="mailto:naushadali.rj@gmail.com">naushadali.rj@gmail.com</a></p>
				<p><strong><?php echo esc_html__( 'GitHub:', 'schema-frameworks' ); ?></strong> <a href="https://github.com/JTechBiz-NaushadA" target="_blank" rel="noopener noreferrer">https://github.com/JTechBiz-NaushadA</a></p>
			</div>
			<div class="card" style="max-width:900px;padding:20px;">
				<h2><?php echo esc_html__( 'What to include in a report', 'schema-frameworks' ); ?></h2>
				<ul class="ul-disc">
					<li><?php echo esc_html__( 'Describe the expected result and what actually happened.', 'schema-frameworks' ); ?></li>
					<li><?php echo esc_html__( 'List clear reproduction steps and the affected page or admin screen, if relevant.', 'schema-frameworks' ); ?></li>
					<li><?php echo esc_html__( 'Include the Schema Frameworks version, WordPress version, PHP version, and relevant SEO/schema plugins or dependencies.', 'schema-frameworks' ); ?></li>
					<li><?php echo esc_html__( 'For diagnostic issues, review the Dashboard and optionally attach its diagnostic CSV export.', 'schema-frameworks' ); ?></li>
				</ul>
				<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=schema-frameworks-dashboard&sf_dashboard_tab=snapshots' ) ); ?>"><?php echo esc_html__( 'Open Dashboard diagnostics', 'schema-frameworks' ); ?></a></p>
			</div>
			<div class="notice notice-warning inline" style="max-width:900px;margin:16px 0 0;padding:8px 12px;">
				<p><strong><?php echo esc_html__( 'Privacy and security reminder', 'schema-frameworks' ); ?></strong></p>
				<p><?php echo esc_html__( 'Before sharing logs, screenshots, URLs, or exports, remove passwords, API keys, access tokens, database credentials, private configuration, personal data, and any other secrets. Share only the information needed to reproduce the issue.', 'schema-frameworks' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function render_graph_debugger_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$report = get_transient( 'schema_frameworks_graph_validation_latest' );
		?>
		<div class="wrap schema-frameworks-admin-page schema-frameworks-graph-debugger">
			<h1><?php echo esc_html__( 'Entity Graph Debugger', 'schema-frameworks' ); ?></h1>
			<p><?php echo esc_html__( 'Read-only inspection of the latest graph captured by an administrator visit. This debugger does not change configuration or public JSON-LD.', 'schema-frameworks' ); ?></p>
			<?php if ( ! is_array( $report ) || empty( $report['nodes'] ) ) : ?>
				<div class="notice notice-info"><p><?php echo esc_html__( 'No graph captured yet. While logged in as an administrator, visit a page that generates Schema Frameworks nodes, then refresh this screen.', 'schema-frameworks' ); ?></p></div>
			<?php else :
			$nodes = array_values( $report['nodes'] );
			$ids = array(); foreach ( $nodes as $n ) { if ( ! empty( $n['id'] ) ) { $ids[ $n['id'] ] = true; } }
			$edges = array();
			foreach ( $nodes as $n ) { if ( ! empty( $n['properties'] ) ) { $this->collect_debug_edges( $n['properties'], $n['id'], $edges ); } }
			?>
			<p><strong><?php echo esc_html__( 'Page:', 'schema-frameworks' ); ?></strong> <a href="<?php echo esc_url( $report['page_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $report['page_url'] ); ?></a><br /><strong><?php echo esc_html__( 'Last captured:', 'schema-frameworks' ); ?></strong> <?php echo esc_html( $report['captured_at'] ); ?></p>
			<h2><?php echo esc_html( sprintf( __( 'Entity inventory (%d)', 'schema-frameworks' ), count( $nodes ) ) ); ?></h2>
			<table class="widefat striped"><thead><tr><th>Module</th><th>Schema type</th><th>Entity @id</th><th>Properties</th></tr></thead><tbody>
			<?php foreach ( $nodes as $i => $n ) : $detail_id = 'sf-graph-node-' . $i; ?>
			<tr><td><code><?php echo esc_html( $n['module'] ?? '' ); ?></code></td><td><?php echo esc_html( $n['type'] ?? 'Unknown' ); ?></td><td><code><?php echo esc_html( $n['id'] ?? '' ); ?></code></td><td><a href="#<?php echo esc_attr( $detail_id ); ?>">Inspect properties</a></td></tr>
			<?php endforeach; ?></tbody></table>
			<h2><?php echo esc_html__( 'Relationship explorer', 'schema-frameworks' ); ?></h2>
			<?php if ( empty( $edges ) ) : ?><p><?php echo esc_html__( 'No explicit @id references were found in the captured node properties.', 'schema-frameworks' ); ?></p><?php else : ?>
			<table class="widefat striped"><thead><tr><th>Source @id</th><th>Property / path</th><th>Target @id</th><th>Target status</th></tr></thead><tbody>
			<?php foreach ( $edges as $edge ) : ?><tr><td><code><?php echo esc_html( $edge['source'] ); ?></code></td><td><code><?php echo esc_html( $edge['path'] ); ?></code></td><td><code><?php echo esc_html( $edge['target'] ); ?></code></td><td><?php echo isset( $ids[ $edge['target'] ] ) ? esc_html__( 'Target present in captured graph', 'schema-frameworks' ) : esc_html__( 'Target not present in captured nodes', 'schema-frameworks' ); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php endif; ?>
			<h2><?php echo esc_html__( 'Entity property details', 'schema-frameworks' ); ?></h2>
			<?php foreach ( $nodes as $i => $n ) : $detail_id = 'sf-graph-node-' . $i; ?>
			<section id="<?php echo esc_attr( $detail_id ); ?>" style="margin:16px 0;padding:16px;background:#fff;border:1px solid #c3c4c7"><h3><?php echo esc_html( $n['type'] ?? 'Unknown' ); ?> — <code><?php echo esc_html( $n['id'] ?? '' ); ?></code></h3><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?php echo esc_html( wp_json_encode( $n['properties'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre></section>
			<?php endforeach; ?>
			<p class="description"><?php echo esc_html__( 'References are identified by @id objects in the captured module nodes. A target absent from this capture may be supplied by another integration or may not be emitted on this page; absence alone is not proof of an invalid Schema.org reference.', 'schema-frameworks' ); ?></p>
			<?php endif; ?>
		</div><?php
	}

	private function debug_safe_schema( $schema ) {
		$json = wp_json_encode( $schema );
		if ( ! is_string( $json ) || strlen( $json ) > 60000 ) { return array( '@type' => $schema['@type'] ?? 'Unknown', '@id' => $schema['@id'] ?? '', '_debug_note' => 'Properties omitted because the node exceeded the diagnostic size limit.' ); }
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function collect_debug_edges( $value, $source_id, &$edges, $path = '' ) {
		if ( ! is_array( $value ) ) { return; }
		if ( isset( $value['@id'] ) && is_string( $value['@id'] ) && '' !== $value['@id'] && $value['@id'] !== $source_id ) {
			$edges[] = array( 'source' => (string) $source_id, 'path' => $path ? $path : '(root)', 'target' => $value['@id'] );
		}
		foreach ( $value as $key => $child ) {
			if ( '@id' === $key ) { continue; }
			$child_path = $path ? $path . '.' . (string) $key : (string) $key;
			if ( is_array( $child ) ) { $this->collect_debug_edges( $child, $source_id, $edges, $child_path ); }
		}
	}

	public function enqueue_assets( $hook ) {

		$is_framework_screen = false !== strpos( $hook, 'schema-frameworks' );
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_page_editor = $screen && 'page' === $screen->post_type && in_array( $screen->base, array( 'post', 'post-new' ), true );
		$is_blog_post_editor = $screen && 'post' === $screen->post_type && in_array( $screen->base, array( 'post', 'post-new' ), true );

		if ( ! $is_framework_screen && ! $is_page_editor && ! $is_blog_post_editor ) {
			return;
		}

		wp_enqueue_style(
			'schema-frameworks-admin',
			SCHEMA_FRAMEWORKS_URL . 'assets/css/admin.css',
			array(),
			SCHEMA_FRAMEWORKS_VERSION
		);

		wp_enqueue_script(
			'schema-frameworks-admin',
			SCHEMA_FRAMEWORKS_URL . 'assets/js/admin.js',
			array(),
			SCHEMA_FRAMEWORKS_VERSION,
			true
		);
		wp_localize_script( 'schema-frameworks-admin', 'SchemaFrameworksBatchScan', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'schema_frameworks_batch_scan' ), 'strings' => array( 'idle' => __( 'No batch scan has been started.', 'schema-frameworks' ), 'working' => __( 'Visiting content URLs in small batches…', 'schema-frameworks' ), 'paused' => __( 'Scan paused. Resume to continue from the last completed batch.', 'schema-frameworks' ), 'completed' => __( 'Scan completed.', 'schema-frameworks' ), 'failed' => __( 'Some content URLs could not be loaded. See the failed count below.', 'schema-frameworks' ), 'error' => __( 'The scan could not continue. Please try Resume or review your server logs.', 'schema-frameworks' ) ) ) );
	}
}