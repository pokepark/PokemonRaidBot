<?php

function create_history_date_msg_keys($current = '(SELECT max(start_time) FROM raids LEFT JOIN attendance ON attendance.raid_id = raids.id WHERE end_time < UTC_TIMESTAMP() AND attendance.id IS NOT NULL)') {
    if(strlen($current) == 7) {
        // Reformat YYYY-MM to DATETIME
        $current = '\''.$current.'-01 00:00:00\'';
    }
    $q = my_query(' SELECT    	DATE_FORMAT(start_time, "%d") as day, DATE_FORMAT(start_time, "%e") as day_disp, DATE_FORMAT('.$current.', "%Y-%m") as current_y_m,
                    if((SELECT count(*) FROM raids left join attendance on attendance.raid_id = raids.id where end_time < UTC_TIMESTAMP() and date_format(start_time, "%Y-%m") = date_format(DATE_SUB('.$current.', INTERVAL 1 MONTH), "%Y-%m") and attendance.id is not null limit 1), date_format(DATE_SUB('.$current.', INTERVAL 1 MONTH), "%Y-%m"), 0) as prev,
                    if((SELECT count(*) FROM raids left join attendance on attendance.raid_id = raids.id where end_time < UTC_TIMESTAMP() and date_format(start_time, "%Y-%m") = date_format(DATE_ADD('.$current.', INTERVAL 1 MONTH), "%Y-%m") and attendance.id is not null limit 1), date_format(DATE_ADD('.$current.', INTERVAL 1 MONTH), "%Y-%m"), 0) as next
                    FROM        raids
                    LEFT JOIN   attendance
                    ON          attendance.raid_id = raids.id
                    WHERE 		end_time < UTC_TIMESTAMP()
                    AND			date_format(start_time, "%Y-%m") = DATE_FORMAT('.$current.', "%Y-%m")
                    AND 		attendance.id IS NOT NULL
                    GROUP BY    DATE_FORMAT(start_time, "%d")
                    ORDER BY    start_time ASC
                    ');
    $day_keys = [];
    $current_y_m = '';
    $prev = $next = false;
    if($q->rowcount() == 0) return false;
    while($date = $q->fetch()) {
        $day_keys[] =   [
                    'text'          => $date['day_disp'],
                    'callback_data' => $date['day'] . ':history:' . $date['current_y_m']
                    ];
        if($current_y_m == '') $current_y_m = $date['current_y_m'];
        if($prev == false) $prev = $date['prev'];
        if($next == false) $next = $date['next'];
    }
    $keys = inline_key_array($day_keys,4);

    $msg = getTranslation('history_title') . CR . CR;
    $msg .= getTranslation('history_displaying_month') . ' ' . getTranslation('month_'.substr($current_y_m,5)) . CR . CR . getTranslation('raid_select_date');

    $nav_keys = [];
    if($prev != 0) $nav_keys[0][] = ['text' => getTranslation('month_'.substr($prev,5)),'callback_data' => '0:history:' . $prev];
    if($next != 0) $nav_keys[0][] = ['text' => getTranslation('month_'.substr($next,5)),'callback_data' => '0:history:' . $next];

    $keys = array_merge($keys, $nav_keys);
    $keys[] = [
        [
            'text'          => getTranslation('abort'),
            'callback_data' => '0:exit:0'
        ]
    ];
    return [$msg, $keys];
}
?>