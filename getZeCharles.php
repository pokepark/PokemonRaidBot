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
include('logic/curl_get_contents.php');

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
        echo 'Error downloading file, no data received!' . PHP_EOL;
    } else {
        $file = fopen($output, "w+");
        fwrite($file, $data);
        fflush($file);
        fclose($file);
        clearstatcache(); // Otherwise filesize will return stale dat
    }

    return $output;
}

// Verify download
function verifyDownload($file, $git_filesize) {
    // File successfully created?
    if(!is_file($file)) {
      echo 'Error downloading file, no output file was found: ' . $file . PHP_EOL;
    } else {
      $real_filesize = filesize($file);
      if ($real_filesize != $git_filesize) {
        echo "Error downloading file, size doesn't match (" . $real_filesize . " != " . $git_filesize . ")!" . PHP_EOL;
      }
    }
}

// Check whether the file exists already and if so, has it been updated since then
function is_updated($path, $file_object) {
  $github_magic_header = "blob 9\x00";
  // If path doesn't already exist, we want an "update"
  if(!is_file($path)){
    return True;
  }

  // If the hash doesn't match, we want an update.
  // The GitHub sha hash includes more than just the file contents,
  // so we need to add a bit of magic.
  $base_contents = file_get_contents($path);
  $old_hash = sha1($file_object['type'] . " " . $file_object['size'] . "\x00" . $base_contents);
  $new_hash = $file_object['sha'];
  return $old_hash != $new_hash;
}

// Start
echo 'Starting!' . PHP_EOL;

// Git urls
$repo_content = 'https://api.github.com/repos/' . $repo_owner . '/' . $repo_name . '/contents/';
$repo_html = 'https://github.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_dir . '/';
$repo_raw = 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_branch . '/' . $repo_dir . '/';

// Git tree lookup
$tree = curl_get_contents($repo_content);
$leaf = json_decode($tree, true);

// Git tree lookup for repo dir
$content = '';
$foldername = basename($repo_html);
echo 'Downloading each file from ' . $repo_html . PHP_EOL;
foreach ($leaf as $l) {
    if($l['name'] == $foldername && $l['type'] == 'dir') {
        $json = curl_get_contents($l['git_url']);
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
          // Only get files that don't exist or where the hash doesn't match
          if(is_updated($destination . $c['path'], $c)) {
            echo 'Downloading ' . $c['path'] . ': ';
            $download_path = downloadFile($repo_raw, $destination, $c['path']);
            echo filesize($download_path) . '/' . $c['size'] . ' bytes' . PHP_EOL;
            verifyDownload($download_path, $c['size']);
          } else {
              echo 'Skipping file: ' . $c['path'] . " (File hasn't changed.)" . PHP_EOL;
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
