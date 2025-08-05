<?php
/**
 * Front Page Template
 *
 * @package Neux-Child
 * @description This template ensures that the content from "tyden.php" is displayed on the homepage.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Načteme a zobrazíme obsah ze šablony tyden.php.
 * Tím zajistíme, že se na úvodní stránce zobrazí týdenní přehled.
 */
require get_stylesheet_directory() . '/tyden.php';

