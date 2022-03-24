<?php
    // Ingressportalbot icon
    $icon = iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4DC));
    $coords = explode('&pll=',$update['message']['entities']['1']['url'])[1];
    $latlon = explode(',', $coords);
    $lat = $latlon[0];
    $lon = $latlon[1];

    $msg_to_rows = explode(PHP_EOL, $update['message']['text']);

    $supported_bots = ['Ingressportalbot', 'PortalMapBot'];
    if(isset($update['message']['via_bot']) && in_array($update['message']['via_bot']['username'], $supported_bots)) {
        $parse_bot = $update['message']['via_bot']['username'];
    }else {
        // Invalid input or unknown bot - send message and end.
        $msg = '<b>' . getTranslation('invalid_input') . '</b>';
        $msg .= CR . CR . getTranslation('not_supported') . SP . getTranslation('or') . SP . getTranslation('internal_error');
        send_message($update['message']['from']['id'], $msg);
        exit();
   }

    // Ingressportalbot
    if($parse_bot == 'Ingressportalbot') {
        // Set portal bot name.
        $botname = '@Ingressportalbot';

        // Get portal name.
        $portal = trim(str_replace($icon . 'Portal:', '', $msg_to_rows[0]));

        // Get portal address.
        $address = trim(explode(':', $msg_to_rows[1], 2)[1]);

        // Split address?
        debug_log($address, 'Address:');
        if(substr_count($address, ',') == 7) {
            // Split address into 6 pieces which are separated by comma:
            // Street Number, Street, Locality, Sublocality, City, State, ZIP Code, Country
            $pieces = explode(',', $address);
            $address = trim($pieces[1]) . SP . trim($pieces[0]) . ', ' . trim($pieces[6]) . SP . trim($pieces[4]);
        } else if(substr_count($address, ',') == 8) {
            // Split address into 7 pieces which are separated by comma:
            // Place, Street Number, Street, Locality, Sublocality, City, State, ZIP Code, Country
            $pieces = explode(',', $address);
            $address = trim($pieces[2]) . SP . trim($pieces[1]) . ', ' . trim($pieces[7]) . SP . trim($pieces[5]);
        }

        // Portal id
        $portal_id = trim(substr($msg_to_rows[(count($msg_to_rows)-1)], 6));

        // Portal image
        $portal_image = $update['message']['entities']['0']['url'];

    // PortalMapBot
    } else if($parse_bot == 'PortalMapBot') {
        // Set portal bot name.
        $botname = '@PortalMapBot';

        // Get portal name.
        $portal = trim(str_replace('(Intel)','', str_replace('(Scanner)','',$msg_to_rows[0])));

        // Check for strange characters at the beginn of the portal name: â<81>£
        // â = 0x00E2
        // <81> = 0x81
        // £ = 0x00A3
        if(strpos($portal, chr(0x00E2) . chr(0x81) . chr(0x00A3)) === 0) {
            // Remove strange characters from portal name.
            $portal = substr($portal, 3);
            debug_log('Strange characters â<81>£ detected and removed from portal name!');
        }

        // Get portal address.
        $address = trim($msg_to_rows[4]);

        // Remove country from address, e.g. ", Netherlands"
        $address = explode(',',$address,-1);
        $address = trim(implode(',',$address));

        // Portal id
        $portal_id = trim($msg_to_rows[(count($msg_to_rows)-1)]);

        // Portal image
        $portal_image = $update['message']['entities']['0']['url'];

   }

    // Empty address? Try lookup.
    if(empty($address)) {
        // Get address.
        $addr = get_address($lat, $lon);
        $address = format_address($addr);
    }

    // Write to log.
    debug_log('Detected message from ' . $botname);
    debug_log($portal, 'Portal:');
    debug_log($coords, 'Coordinates:');
    debug_log($lat, 'Latitude:');
    debug_log($lon, 'Longitude:');
    debug_log($address, 'Address:');
    debug_log($portal_id, 'Portal id:');

?>
