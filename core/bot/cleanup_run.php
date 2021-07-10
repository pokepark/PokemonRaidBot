<?php
// Cleanup request received.
if (isset($update['cleanup']) && $config->CLEANUP) {
    cleanup_log('Cleanup request received...');
    // Check access to cleanup of bot
    if ($update['cleanup']['secret'] == $config->CLEANUP_SECRET) {
        // Get telegram cleanup value if specified.
        if (isset($update['cleanup']['telegram'])) {
            $telegram = $update['cleanup']['telegram'];
        } else {
            $telegram = 2;
        }
        // Get database cleanup value if specified.
        if (isset($update['cleanup']['database'])) {
            $database = $update['cleanup']['database'];
        } else {
            $database = 2;
        }
        if (function_exists('run_cleanup')) {
            // Write cleanup info to database.
            cleanup_log('Running cleanup now!');
            // Run cleanup
            run_cleanup($telegram, $database);
        } else {
            error_log('No function found to run cleanup!');
            cleanup_log('Add a function named "run_cleanup" to run cleanup for telegram messages and database entries!', 'ERROR:');
            cleanup_log('Arguments of that function need to be values to run/not run the cleanup for telegram $telegram and the database $database.', 'ERROR:');
            cleanup_log('For example: function run_cleanup($telegram, $database)', 'ERROR:');
            http_response_code(404);
        }
    } else {
        $error = 'Cleanup authentication failed.';
        http_response_code(403);
        error_log($error);
        die($error);
    }
    // Exit after cleanup
    exit();
} else if (isset($update['cleanup'])) {
    $error = 'Valid cleanup request received but cleanup is not enabled and will not be done!';
    error_log($error);
    http_response_code(409);
    die($error);
}
