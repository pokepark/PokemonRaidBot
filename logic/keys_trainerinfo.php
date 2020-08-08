<?php
/**
 * Keys trainer info.
 * @show bool
 * @return array
 */
function keys_trainerinfo($show = false)
{
    global $config;
    // Toggle state.
    $status = 'show';
    if($show || !$config->TRAINER_BUTTONS_TOGGLE) {
        // Always show buttons?
        if(($show == true && !$config->TRAINER_BUTTONS_TOGGLE) || $config->TRAINER_BUTTONS_TOGGLE) {
            $status = 'hide';
        }

        // Keys to set team and level
        $keys = [
            [
                [
                    'text'          => getPublicTranslation('trainerinfo'),
                    'callback_data' => 'trainer:vote_level:' . $status
                ],
            ],
            [
                [
                    'text'          => getPublicTranslation('team') . SP . TEAM_B,
                    'callback_data' => 'trainer:vote_team:mystic'
                ],
                [
                    'text'          => getPublicTranslation('team') . SP . TEAM_R,
                    'callback_data' => 'trainer:vote_team:valor'
                ],
                [
                    'text'          => getPublicTranslation('team') . SP . TEAM_Y,
                    'callback_data' => 'trainer:vote_team:instinct'
                ],
            ],
            [
                [
                    'text'          => getPublicTranslation('level') . ' +',
                    'callback_data' => 'trainer:vote_level:up'
                ],
                [
                    'text'          => getPublicTranslation('level') . ' -',
                    'callback_data' => 'trainer:vote_level:down'
                ]
            ]
        ];
    } else {
        // Key to show/hide trainer info.
        $keys = [
            [
                [
                    'text'          => getPublicTranslation('trainerinfo'),
                    'callback_data' => 'trainer:vote_level:' . $status
                ],
            ]
        ];
    }

    return $keys;
}

?>
