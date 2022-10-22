<?php
/**
 * Edit gym keys.
 * @param $update The global update-variable containing the query from Telegram
 * @param $gym_id Gym id
 * @param $show_gym Current show_gym value of the gym
 * @param $ex_gym Current ex_gym value of the gym
 * @param $gym_note Current gym note
 * @param $gym_address Current gym address
 * @return array
 */
function edit_gym_keys($update, $gym_id, $show_gym, $ex_gym, $gym_note, $gym_address)
{
    global $botUser;
    // Hide gym?
    if($show_gym == 1) {
        $text_show_button = getTranslation('hide_gym');
        $arg_show = 0;

    // Show gym?
    } else {
        $text_show_button = getTranslation('show_gym');
        $arg_show = 1;
    }

    // Ex-raid gym?
    if($ex_gym == 1) {
        $text_ex_button = getTranslation('normal_gym');
        $arg_ex = 0;

    // Normal gym?
    } else {
        $text_ex_button = getTranslation('ex_gym');
        $arg_ex = 1;
    }

    // Add buttons to show/hide the gym and add/remove ex-raid flag
    $keys = [];
    $keys[] = [
        [
            'text'          => $text_show_button,
            'callback_data' => $gym_id . ':gym_edit_details:show-' . $arg_show
        ],
        [
            'text'          => $text_ex_button,
            'callback_data' => $gym_id . ':gym_edit_details:ex-' . $arg_ex
        ]
    ];
    if($botUser->accessCheck($update, 'gym-name', true)) {
        $keys[] = [
            [
            'text'          => EMOJI_PENCIL . ' ' . getTranslation("gym_name_edit"),
            'callback_data' => $gym_id . ':gym_edit_details:name'
            ]
        ];
    }
    if($botUser->accessCheck($update, 'gym-note', true)) {
        $keys[] = [
            [
            'text'          => EMOJI_INFO . ' ' . (!empty($gym_note) ? getTranslation("edit") : getTranslation("add") ) . ' ' . getTranslation("gym_add_edit_note"),
            'callback_data' => $gym_id . ':gym_edit_details:note'
            ]
        ];
    }
    if($botUser->accessCheck($update, 'gym-address', true)) {
        $keys[] = [
            [
            'text'          => EMOJI_MAP . ' ' . ((!empty($gym_address) && $gym_address != getTranslation("forest")) ? getTranslation("edit") : getTranslation("add") ) . ' ' . getTranslation("gym_address"),
            'callback_data' => $gym_id . ':gym_edit_details:addr'
            ]
        ];
    }
    if($botUser->accessCheck($update, 'gym-gps', true)) {
        $keys[] = [
            [
            'text'          => EMOJI_HERE . ' ' . getTranslation("gym_edit_coordinates"),
            'callback_data' => $gym_id . ':gym_edit_details:gps'
            ]
        ];
    }
    if($botUser->accessCheck($update, 'gym-delete', true)) {
        $keys[] = [
            [
            'text'          => EMOJI_DELETE . ' ' . getTranslation("gym_delete"),
            'callback_data' => '0:gym_delete:'.$gym_id.'-delete'
            ]
        ];
    }
    $keys[] = [
        [
        'text'          => getTranslation('done'),
        'callback_data' => '0:exit:1'
        ]
    ];

    return $keys;
}
?>
