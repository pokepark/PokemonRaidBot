<?php
    // Ingressportalbot icon
    $icon = iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4DC));
    $coords = explode('&pll=',$update['message']['entities']['1']['url'])[1];
    $latlon = explode(',', $coords);
    $lat = $latlon[0];
    $lon = $latlon[1];
    // Ingressportalbot
    if(strpos($update['message']['text'], $icon . 'Portal:') === 0) {
        // Set portal bot name.
        $botname = '@Ingressportalbot';

        // Get portal name.
        $portal = trim(str_replace($icon . 'Portal:', '', strtok($update['message']['text'], PHP_EOL)));

        // Get portal address.
        $address = explode(PHP_EOL, $update['message']['text'])[1];
        $address = trim(explode(':', $address, 2)[1]);

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

        // Portal image
        $portal_image = $update['message']['entities']['0']['url'];

    // PortalMapBot
    } else if(substr_compare(strtok($update['message']['text'], PHP_EOL), '(Intel)', -strlen('(Intel)')) === 0) {
        // Set portal bot name.
        $botname = '@PortalMapBot';

        // Get portal name.
        $portal = trim(substr(strtok($update['message']['text'], PHP_EOL), 0, -strlen('(Intel)')));

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
        $address = trim(explode(PHP_EOL, $update['message']['text'])[4]);

        // Remove country from address, e.g. ", Netherlands"
        $address = explode(',',$address,-1);
        $address = trim(implode(',',$address));

        // Portal image
        $portal_image = $update['message']['entities']['0']['url'];

   } else {
        // Invalid input or unknown bot - send message and end.
        $msg = '<b>' . getTranslation('invalid_input') . '</b>';
        $msg .= CR . CR . getTranslation('not_supported') . SP . getTranslation('or') . SP . getTranslation('internal_error');
        sendMessage($update['message']['from']['id'], $msg);
        exit();
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

?>
