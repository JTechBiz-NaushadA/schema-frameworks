<?php
/**
 * Plugin Name: Schema Frameworks
 * Plugin URI: https://github.com/JTechBiz-NaushadA
 * Description: Schema.org structured data management, modular schema controls, post and page schema mapping, custom FAQ schema, diagnostic scanning, filtered CSV exports.
 * Version: 1.7.9
 * Author: Naushad A.
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schema-frameworks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'SCHEMA_FRAMEWORKS_VERSION', '1.7.9' );
define( 'SCHEMA_FRAMEWORKS_FILE', __FILE__ );
define( 'SCHEMA_FRAMEWORKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCHEMA_FRAMEWORKS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the plugin classes.
 */
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-config.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-registry.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-schema-manager.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-content-schema-context.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-page-schema-context.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-post-type-mapping-recommender.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-post-schema-module.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-entity-identity.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-entity-reference.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-graph-assembler.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-graph-validator.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/interface-condition.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-request-context.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-value.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-manager.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-singular.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-archive.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-front-page.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-page.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-post.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-post-type.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-page-id.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-url.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-path.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-category.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/conditions/class-condition-tag.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-organization-resolver.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/interface-schema.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-software-application.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-product.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-service.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-faq.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-howto.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-tech-article.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-digital-document.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-defined-term.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-web-application.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-webpage.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-breadcrumb.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-image-object.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-organization-logo.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-person.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-post-content-schema.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-comment-schema.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/schema/class-organization.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/helpers/class-relationships.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/integrations/interface-integration.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/integrations/class-integration-detector.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/integrations/adapters/class-yoast-organization.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/integrations/class-yoast.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-settings.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-schema-configuration.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-page-editor.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-post-type-mappings.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-post-content-editor.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/admin/class-admin.php';
require_once SCHEMA_FRAMEWORKS_PATH . 'includes/class-plugin.php';

/**
 * Start the plugin.
 */
function schema_frameworks() {
	return SchemaFrameworks\Plugin::instance();
}

schema_frameworks();