<?php
namespace PointsPlus\Emails;

defined('ABSPATH') || exit;

/**
 * Load and return the subject line for a given slug.
 *
 * @param string $slug   e.g. 'reload-completed'
 * @param array  $ctx    variables to extract into the template
 * @return string
 */
function get_email_subject( string $slug, array $context = [] ): string {
    $file = plugin_dir_path( __DIR__ )
          . 'emails/subjects/' 
          . sanitize_file_name( "$slug-subject" )
          . '.php';

    if ( ! file_exists( $file ) ) {
        return '';
    }

    extract( $context, EXTR_SKIP );
    return (string) include $file;
}

/**
 * Render an email template.
 *
 * @param string $slug    Template slug (e.g. 'reload-completed').
 * @param array  $context Variables to extract inside the template.
 * @return string         The rendered HTML/text.
 */
function get_email_body( string $slug, array $context = [] ): string {
    $file = plugin_dir_path( __DIR__ ) 
              . 'emails/templates/' 
              . sanitize_file_name( "$slug-body" ) 
              . '.php';

    if ( ! file_exists( $file ) ) {
        return '';
    }

    // Bring context into local scope
    extract( $context, EXTR_SKIP );

    // Capture output
    ob_start();
    include $file;
    return ob_get_clean();
}
