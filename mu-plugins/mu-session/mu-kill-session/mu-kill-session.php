<?php
/**
 * Plugin Name: MU Clear Session Once
 * Description: Тимчасово очищує та завершує сесію (одноразово при 1 запиті).
 */

add_action('init', function () {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();

        // Якщо активний cookie — видаляємо його
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_write_close();
        error_log('[MU Clear Session] PHP session destroyed successfully.');
    } else {
        error_log('[MU Clear Session] Session was not active.');
    }

    // ⚠️ Самознищення плагіна — видалити себе після виконання (не обов’язково)
    $file = __FILE__;

/**    
    register_shutdown_function(function () use ($file) {
        @unlink($file);
        error_log('[MU Clear Session] Plugin file self-deleted: ' . $file);
    });
*/

});
