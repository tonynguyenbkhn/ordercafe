<?php

namespace TWMP_THEME\Inc\Helpers;

function autoloader( $resource = '' ) {

    $resource_path  = false;
    $namespace_root = 'TWMP_THEME\\';
    $resource       = trim( $resource, '\\' );

    if ( empty( $resource ) || strpos( $resource, '\\' ) === false || strpos( $resource, $namespace_root ) !== 0 ) {
        // Not our namespace, bail out.
        return;
    }

    // Remove our root namespace.
    $resource = str_replace( $namespace_root, '', $resource );
    $path = explode(
        '\\',
        str_replace( '_', '-', strtolower( $resource ) )
    );
    if ( empty( $path[0] ) || empty( $path[1] ) ) {
        return;
    }

    $directory = '';
    $file_name = '';

    if ( 'inc' === $path[0] ) {

        switch ( $path[1] ) {
            case 'traits':
                $directory = 'traits';
                $file_name = sprintf( 'trait-%s', trim( strtolower( $path[2] ) ) );
                break;

            case 'widgets':
            case 'blocks': // phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
                if ( ! empty( $path[2] ) ) {
                    $directory = sprintf( 'classes/%s', $path[1] );
                    $file_name = sprintf( 'class-%s', trim( strtolower( $path[2] ) ) );
                    break;
                }
            default:
                $directory = 'classes';
                $file_name = sprintf( 'class-%s', trim( strtolower( $path[1] ) ) );
                break;
        }

        $resource_path = sprintf( '%s/inc/%s/%s.php', untrailingslashit( TWMP_DIR_PATH ), $directory, $file_name );

    }
    $is_valid_file = validate_file( $resource_path );

    if ( ! empty( $resource_path ) && file_exists( $resource_path ) && ( 0 === $is_valid_file || 2 === $is_valid_file ) ) {
        // We already making sure that file is exists and valid.
        require_once( $resource_path ); // phpcs:ignore
    }

}

spl_autoload_register( '\TWMP_THEME\Inc\Helpers\autoloader' );
