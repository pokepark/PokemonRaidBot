<?php
/**
 * Download Portal image.
 * @param $img_url
 * @param $action
 * @param $update
 * @param $chats
 * @param $prefix_text
 * @param $hide
 * @return array
 */
function download_Portal_Image($img_url, $destination, $filename) {
    // Output filename.
    $output = $destination . '/' . $filename;

    // Write to log.
    debug_log($img_url, 'Portal Image URL:');
    debug_log($output, 'Portal Image download destination:');

    // Get file.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $img_url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close ($ch);

    // Write to file.
    if(empty($result)) {
        info_log($img_url, 'Failed to download Portal image:');
        return false;
    } else {
        debug_log('Downloading portal image!');
        $file = fopen($output, "w+");
        fwrite($file, $result);
        fclose($file);
        $filesize = filesize($output);
        debug_log($filesize, 'Portal image filesize:');
        return $output;
    }
}
?>
