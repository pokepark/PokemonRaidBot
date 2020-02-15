<?php
// File settings.
$destination = __DIR__ . '/images/pokemon/';
$filter = ".png";

// Git Repo settings.
$repo_owner = 'ZeChrales';
$repo_name = 'PogoAssets';
$repo_dir = 'pokemon_icons';
$repo_branch = 'master';

// Get JSON
function getRepoContent($URL) {
    // Get data.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_USERAGENT, "https://developer.github.com/v3/#user-agent-required" );
    curl_setopt($ch, CURLOPT_USERAGENT, "Googlebot/2.1 (+http://www.google.com/bot.html)" );
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

// Download file
function downloadFile($URL, $destination, $filename) {
    // Input and output filename.
    $input = $URL . $filename;
    $output = $destination . $filename;

    // Get file.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $input);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close ($ch);

    // Write to file.
    if(empty($data)) {
        echo 'Error downloading file!' . PHP_EOL;
    } else {
        $file = fopen($output, "w+");
        fwrite($file, $data);
        fclose($file);
    }

    return $output;
}

// Verify download
function verifyDownload($file, $git_filesize) {
    // File successfully created?
    if(!(is_file($file) && filesize($file) == $git_filesize)) {
        echo 'Error downloading file!' . PHP_EOL;
    }
}

// Start
echo 'Starting!' . PHP_EOL;

// Git urls
$repo_content = 'https://api.github.com/repos/' . $repo_owner . '/' . $repo_name . '/contents/';
$repo_html = 'https://github.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_dir . '/';
$repo_raw = 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_branch . '/' . $repo_dir . '/';

// Git tree lookup
$tree = getRepoContent($repo_content);
$leaf = json_decode($tree, true);

// Git tree lookup for repo dir
$content = '';
$foldername = basename($repo_html);
echo 'Downloading each file from ' . $repo_html . PHP_EOL;
foreach ($leaf as $l) {
    if($l['name'] == $foldername && $l['type'] == 'dir') {
        $json = getRepoContent($l['git_url']);
        $content = json_decode($json, true);
        break;
    }
}

// Download each file.
if(is_array($content)) {
    foreach($content['tree'] as $c) {
        // Filter by file extension
        $ext = '.' . pathinfo($c['path'], PATHINFO_EXTENSION);
        if($filter == $ext) {
          // Filter by files that already exist
          if(!is_file($destination . $c['path'])) {
            echo 'Downloading ' . $c['path'] . ': ';
            $download = downloadFile($repo_raw, $destination, $c['path']);
            echo filesize($download) . '/' . $c['size'] . ' bytes' . PHP_EOL;
            verifyDownload($download, $c['size']);
          } else {
              echo 'Skipping file: ' . $c['path'] . ' (Already exists)' . PHP_EOL;
          }
        } else {
            echo 'Skipping file: ' . $c['path'] . ' (File extension filtering)' . PHP_EOL;
        }
    }
} else {
    echo "Failed to download repo content!";
}

echo "Finished!" . PHP_EOL;
?>
