<?php
/**
 * Run raids cleanup.
 * @param $telegram
 * @param $database
 */
function run_cleanup ($telegram = 2, $database = 2) {
    global $config;
    // Check configuration, cleanup of telegram needs to happen before database cleanup!
    if ($config->CLEANUP_TIME_TG > $config->CLEANUP_TIME_DB) {
        cleanup_log('Configuration issue! Cleanup time for telegram messages needs to be lower or equal to database cleanup time!');
        cleanup_log('Stopping cleanup process now!');
        exit;
    }

    /* Check input
     * 0 = Do nothing
     * 1 = Cleanup
     * 2 = Read from config
    */

    // Get cleanup values from config per default.
    if ($telegram == 2) {
        $telegram = ($config->CLEANUP_TELEGRAM) ? 1 : 0;
    }

    if ($database == 2) {
        $database = ($config->CLEANUP_DATABASE) ? 1 : 0;
    }
    $now = utcnow();
    // Start cleanup when at least one parameter is set to trigger cleanup
    if ($telegram == 1 || $database == 1) {
        // Query for telegram cleanup without database cleanup
        if ($telegram == 1) {
            // Get cleanup info for telegram cleanup.
            $rs = my_query('
                SELECT    cleanup.id, cleanup.raid_id, cleanup.chat_id, cleanup.message_id, gyms.gym_name, raids.gym_id
                FROM      cleanup
                    LEFT JOIN   raids 
                    ON          cleanup.raid_id = raids.id 
                    LEFT JOIN   gyms
                    ON          raids.gym_id = gyms.id
                WHERE     raids.end_time < DATE_SUB(\''.$now.'\', INTERVAL '.$config->CLEANUP_TIME_TG.' MINUTE)
                ');
            $cleanup_ids = [];
            $cleanup_gyms = [];
            $tg_json = [];
            $remote_string = getPublicTranslation('remote_raid');
            cleanup_log('Telegram cleanup starting. Found ' . $rs->rowCount() . ' entries for cleanup.');
            if($rs->rowCount() > 0) {
                while($row = $rs->fetch()) {
                    $tg_json[] = delete_message($row['chat_id'], $row['message_id'], true);
                    cleanup_log('Deleting raid: '.$row['raid_id'].' from chat '.$row['chat_id'].' (message_id: '.$row['message_id'].')');
                    $cleanup_ids[] = $row['id'];
                    if(substr($row['gym_name'],0,strlen($remote_string)) == $remote_string) {
                        $cleanup_gyms[] = $row['gym_id'];
                        cleanup_log('Deleting temporary gym ' . $row['gym_id'] . ' from database.');
                    }
                }
                my_query('DELETE FROM cleanup WHERE id IN (' . implode(',', $cleanup_ids) . ')');
                if(count($cleanup_gyms) > 0) {
                    my_query('DELETE FROM gyms WHERE id IN (' . implode(',', $cleanup_gyms) . ')');
                }
                curl_json_multi_request($tg_json);
            }
        }
        if($database == 1) {
            cleanup_log('Database cleanup called.');
            $q_a = my_query('DELETE FROM attendance WHERE raid_id IN (SELECT id FROM raids WHERE raids.end_time < DATE_SUB(\''.$now.'\', INTERVAL '.$config->CLEANUP_TIME_DB.' MINUTE))');
            $q_r = my_query('DELETE FROM raids WHERE end_time < DATE_SUB(\''.$now.'\', INTERVAL '.$config->CLEANUP_TIME_DB.' MINUTE)');
            cleanup_log('Cleaned ' . $q_a->rowCount() . ' rows from attendance table');
            cleanup_log('Cleaned ' . $q_r->rowCount() . ' rows from raids table');
        }
        // Write to log.
        cleanup_log('Finished with cleanup process!');
    }else {
        cleanup_log('Cleanup was called, but nothing was done. Check your config and cleanup request for which actions you would like to perform (Telegram or database cleanup)');
    }
}

?>
