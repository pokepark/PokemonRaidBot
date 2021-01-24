<?php
// File settings.
$destination = __DIR__ . '/images/';
$filter = ".png";

// Set different destination via argument
if(!empty($argv[1])) {
    $destination = $argv[1];
}

// Git Repo array.
$repos = [];

// ZeChrales
if(empty($argv[2]) || (!empty($argv[2]) && strtolower($argv[2]) == "zechrales")) {
    $repos[] = array('owner'  => "ZeChrales", 
                     'name'   => "PogoAssets", 
                     'branch' => "master", 
                     'dir'    => "pokemon_icons");
}

// PokeMiners
if(empty($argv[2]) || (!empty($argv[2]) && strtolower($argv[2]) == "pokeminers")) {
    $repos[] = array('owner'   => "PokeMiners", 
                      'name'   => "pogo_assets", 
                      'branch' => "master", 
                      'dir'    => "Images/Pokemon - 256x256");
}

// Get download function curl_get_contents
include('logic/curl_get_contents.php');

// Download file
function downloadFile($URL, $destination, $filename) {
    // Input and output filename.
    $input = $URL . $filename;
    $output = $destination . $filename;

    // Get file.
    $data = curl_get_contents($input);

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

// Process each repo
foreach ($repos as $key => $r)
{
    $repo_owner = $r['owner'];
    $repo_name = $r['name'];
    $repo_branch = $r['branch'];
    $repo_dir = $r['dir'];
    $dest = $destination . 'pokemon_' . $repo_owner .'/';

    // Set destination to different path
    if(!empty($argv[1]) && !empty($argv[2])) {
        $dest = rtrim($destination,"/") . '/';
    }

    // Content dir
    $content_dir = '';
    if (strpos($repo_dir, '/') !== false) {
        $content_dir = substr($repo_dir, 0, strrpos($repo_dir, '/'));
    }

    // Raw download dir
    $raw_dir = $repo_dir;
    if (strpos($repo_dir, ' ') !== false) {
        $raw_dir = str_replace(' ', '%20', $repo_dir);
    }

    // Git urls
    $repo_content = 'https://api.github.com/repos/' . $repo_owner . '/' . $repo_name . '/contents/' . $content_dir;
    $repo_html = 'https://github.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_dir . '/';
    //$repo_raw = 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_branch . '/' . $repo_dir . '/';
    $repo_raw = 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_branch . '/' . $raw_dir . '/';

    // Git tree lookup
    $tree = curl_get_contents($repo_content);
    $leaf = json_decode($tree, true);

    // Debug
    //echo 'LEAF:' . PHP_EOL;
    //print_r($leaf) . PHP_EOL;

    // Git tree lookup for repo dir
    $content = '';
    $foldername = basename($repo_html);
    echo 'Downloading each file from ' . $repo_html . PHP_EOL;
    echo "Repo raw: " . $repo_raw . PHP_EOL;
    foreach ($leaf as $l) {
        if($l['name'] == $foldername && $l['type'] == 'dir') {
            $json = curl_get_contents($l['git_url']);
            $content = json_decode($json, true);
            break;
        }
    }

    // Download each file.
    if(is_array($content)) {
        echo "Downloading repo content." . PHP_EOL;
        echo "Repo content: " . $repo_content . PHP_EOL;
        foreach($content['tree'] as $c) {
            // Filter by file extension
            $ext = '.' . pathinfo($c['path'], PATHINFO_EXTENSION);
            if($filter == $ext) {
              // Only get files that don't exist or where the hash doesn't match
              if(is_updated($dest . $c['path'], $c)) {
                echo 'Downloading ' . $c['path'] . ': ';
                $download_path = downloadFile($repo_raw, $dest, $c['path']);
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
        echo "Failed to download repo content!" . PHP_EOL;
        echo "Repo content: " . $repo_content . PHP_EOL;
    }
}

echo "Finished!" . PHP_EOL;
?>
