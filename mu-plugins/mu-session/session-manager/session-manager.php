<?php
/**
 * Plugin Name: MU Session Manager
 * Description: Safe session_start/session_write_close handler with logging for plugins using $_SESSION (e.g., Monobank, GiveWP).
 * Author: ITcxUA
 * Version: 1.1
 */

// 🔧 Функція логування (тільки якщо WP_DEBUG увімкнено)
function sm_log( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$timestamp = date( 'Y-m-d H:i:s' );
		error_log( "[SessionManager][$timestamp] $message" );
	}
}

// Старт сесії на init
add_action( 'init', function () {
	if ( session_status() === PHP_SESSION_NONE ) {
		session_start();
		sm_log( 'session_start() виконано.' );
	} else {
		sm_log( 'session вже активна. Старт пропущено.' );
	}
}, 1 );

// Закриття сесії на shutdown
add_action( 'shutdown', function () {
	if ( session_status() === PHP_SESSION_ACTIVE ) {
		session_write_close();
		sm_log( 'session_write_close() виконано.' );
	} else {
		sm_log( 'session вже закрита. Закриття пропущено.' );
	}
}, PHP_INT_MAX );
