<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Funkce pro načtení stylů rodičovské a child šablony.
 */
function pps_enqueue_parent_child_styles() {
    $parent_style_handle = 'neux-style'; 
    $parent_theme_uri = get_template_directory_uri();
    
    // Načte hlavní styl rodičovské šablony
    wp_enqueue_style( $parent_style_handle, $parent_theme_uri . '/style.css' );
    
    // Načte hlavní styl child šablony
    wp_enqueue_style( 'neux-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style_handle )
    );

    // --- KLÍČOVÁ ZMĚNA: Načteme náš JS skript globálně ---
    // Tento skript se teď načte na každé stránce webu.
    wp_enqueue_script(
        'vlastni-mobilni-menu-js', // Unikátní název pro náš skript
        get_stylesheet_directory_uri() . '/js/tyden.js',
        array('jquery'), // Závislost na jQuery pro jistotu
        null, // Verze
        true // Načíst v patičce
    );
}
add_action( 'wp_enqueue_scripts', 'pps_enqueue_parent_child_styles' );


/**
 * Funkce pro vložení HTML tlačítka pro mobilní menu hned na začátek těla stránky.
 * Díky tomu bude tlačítko na každé stránce.
 */
function pps_vlozit_mobilni_toggle() {
    // HTML pro naše tlačítko ("hamburger")
    echo '<div id="vlastni-mobilni-toggle"><span></span><span></span><span></span></div>';
}
add_action( 'wp_body_open', 'pps_vlozit_mobilni_toggle' );

?>