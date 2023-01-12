<?php
/**
 * Plugin Name: AST Template View On Pages Table
 * Plugin URI: https://github.com/mirekdlugosz/ast-template-view/
 * Description: Allow page authors to mark specific pages as templates - used in internal process
 * Version: 2022.12.11
 * Author: Mirek Długosz
 * Author URI: https://mirekdlugosz.com/
 * Text Domain: associationforsoftwaretesting
 * Update URI: https://github.com/mirekdlugosz/ast-template-view/
 *
 * @package AssociationForSoftwareTesting\TemplateView
 */

const PAGE_TEMPLATE_META_KEY = 'ast_page_is_template';
const VIEWS_QUERY_VAR        = 'ast_custom_filter';


/**
 * Register custom post meta key.
 */
function ast_register_post_meta() {
	foreach ( array( 'page', 'post' ) as $post_type ) {
		register_post_meta(
			$post_type,
			PAGE_TEMPLATE_META_KEY,
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'boolean',
			)
		);
	}
}

/**
 * Register page edit sidebar component.
 */
function ast_register_block_type() {
	register_block_type( __DIR__ . '/dist/mark-page-as-template/' );
}


/**
 * Run plugin initialization - register all plugin components.
 */
function ast_template_view_plugin_init() {
	ast_register_post_meta();
	ast_register_block_type();
}

add_action( 'init', 'ast_template_view_plugin_init' );


/**
 * Tell WordPress about URL query variable we can handle.
 *
 * @param array $query_vars See WordPress docs.
 */
function ast_query_vars( $query_vars ) {
	$query_vars[] = VIEWS_QUERY_VAR;
	return $query_vars;
}

add_filter( 'query_vars', 'ast_query_vars' );


/**
 * Add link at the top of pages table.
 * This is modeled after constructor in wp-admin/includes/class-wp-posts-list-table.php
 *
 * @param array $views See WordPress docs.
 */
function ast_views_edit_page( $views ) {
	global $wpdb;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_type = $_GET['post_type'] ?? 'post';

	$ast_view_args = array(
		'post_type'     => $post_type,
		VIEWS_QUERY_VAR => PAGE_TEMPLATE_META_KEY,
	);

	$url = esc_url( add_query_arg( $ast_view_args, 'edit.php' ) );

	$posts_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT( 1 )
            FROM $wpdb->posts AS posts
            JOIN $wpdb->postmeta AS pmeta
            ON posts.ID = pmeta.post_id
            WHERE post_status = 'draft'
            AND posts.post_type = %s
            AND pmeta.meta_key = %s
            AND pmeta.meta_value = 1",
			$post_type,
			PAGE_TEMPLATE_META_KEY
		)
	);

	$label = sprintf(
		/* translators: %s: Number of posts. */
		_nx(
			'Templates <span class="count">(%s)</span>',
			'Templates <span class="count">(%s)</span>',
			$posts_count,
			'associationforsoftwaretesting'
		),
		number_format_i18n( $posts_count )
	);

	$is_current = get_query_var( VIEWS_QUERY_VAR ) === PAGE_TEMPLATE_META_KEY;

	$full_link = sprintf(
		'<a href="%s"%s>%s</a>',
		$url,
		$is_current ? ' class="current" aria-current="page"' : '',
		$label
	);

	$views[ PAGE_TEMPLATE_META_KEY ] = $full_link;
	return $views;
}

add_filter( 'views_edit-page', 'ast_views_edit_page' );
add_filter( 'views_edit-post', 'ast_views_edit_page' );


/**
 * Modify WPQuery params based on URL query values.
 * WordPress calls this for *all* WPQuery calls,
 * so we need to be careful to not impact the performance
 * of entire website.
 *
 * @param array $query See WordPress docs.
 */
function ast_pre_get_posts( $query ) {
	// viewers shouldn't pay price of the plugin.
	if ( ! is_admin() ) {
		return $query;
	}

	global $pagenow;

	if ( 'edit.php' !== $pagenow ) {
		return $query;
	}

	if ( get_query_var( VIEWS_QUERY_VAR ) !== PAGE_TEMPLATE_META_KEY ) {
		return $query;
	}

	$query->query_vars['post_status'] = 'draft';
	$query->query_vars['meta_key']    = PAGE_TEMPLATE_META_KEY;
	$query->query_vars['meta_value']  = 1;

	return $query;
}

add_filter( 'pre_get_posts', 'ast_pre_get_posts' );
