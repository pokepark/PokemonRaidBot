<?php
// File settings.
$destination = __DIR__ . '/images/';
$filter = ".png";

// Set different destination via argument
if(!empty($argv[2])) {
    $destination = $argv[2];
}

// Git Repo array.
$repos = [];

// ZeChrales
if(empty($argv[1]) || (!empty($argv[1]) && strtolower($argv[1]) == "zechrales")) {
    $repos[] = array('owner'  => "ZeChrales", 
                     'name'   => "PogoAssets", 
                     'branch' => "master", 
                     'dir'    => "pokemon_icons");
}

// PokeMiners
if(empty($argv[1]) || (!empty($argv[1]) && strtolower($argv[1]) == "pokeminers")) {
    $repos[] = array('owner'   => "PokeMiners", 
                      'name'   => "pogo_assets", 
                      'branch' => "master", 
                      'dir'    => "Images/Pokemon - 256x256");
}

// Get download function curl_get_contents
include('logic/curl_get_contents.php');

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
echo 'Starting fetch of missing pokemon images' . PHP_EOL;

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

    // Make sure destination exists otherwise create it
    if (!file_exists($dest)) {
        mkdir($dest);
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
    $repo_raw = 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/' . $repo_branch . '/' . $raw_dir . '/';

    // Git tree lookup
    $tree = curl_get_contents($repo_content);
    $leaf = json_decode($tree, true);
    // Detect rate-limiting and die gracefully
    if(is_array($leaf) && in_array('message', $leaf)) {
      die('Failed to download repo index: ' . $leaf['message']);
    }

    // Debug
    //echo 'LEAF:' . PHP_EOL;
    //print_r($leaf) . PHP_EOL;

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
        $count_unchanged = 0;
        $count_extension = 0;
        $i = 0;
        $multi_handle = curl_multi_init();
        $file_pointers = [];
        $curl_handles = [];
        $output_info = [];
        foreach($content['tree'] as $c) {
            // Filter by file extension
            $ext = '.' . pathinfo($c['path'], PATHINFO_EXTENSION);
            if($filter == $ext) {
              // Only get files that don't exist or where the hash doesn't match
              if(is_updated($dest . $c['path'], $c)) {
                echo 'Downloading ' . $c['path'] . PHP_EOL;
                $input = $repo_raw . $c['path'];
                $output = $dest . $c['path'];

                $output_info[] = ['file'=>$output, 'source_size'=>$c['size']];
                $curl_handles[$i] = curl_init($input);
                $file_pointers[$i] = fopen($output, 'w');
                curl_setopt($curl_handles[$i], CURLOPT_FILE, $file_pointers[$i]);
                curl_setopt($curl_handles[$i], CURLOPT_HEADER, 0);
                curl_setopt($curl_handles[$i], CURLOPT_CONNECTTIMEOUT, 60);
                curl_multi_add_handle($multi_handle,$curl_handles[$i]);
                $i++;
              } else {
                  $count_unchanged = $count_unchanged + 1;
                  // Debug
                  // echo 'Skipping file: ' . $c['path'] . " (File hasn't changed.)" . PHP_EOL;
              }
            } else {
                $count_extension = $count_extension + 1;
                // Debug
                // echo 'Skipping file: ' . $c['path'] . ' (File extension filtering)' . PHP_EOL;
            }
        }
        // Download the files
        do {
            curl_multi_exec($multi_handle,$running);
        } while ($running > 0);

        $successfull_count = 0;
        for($o=0;$o<$i;$o++) {
            curl_multi_remove_handle($multi_handle, $curl_handles[$o]);
            curl_close($curl_handles[$o]);
            fclose ($file_pointers[$o]);

            // Verify download
            // File successfully created?
            if(!is_file($output_info[$o]['file'])) {
              echo 'Error downloading file, no output file was found: ' . $output_info[$o]['file'] . PHP_EOL;
            } else {
              $real_filesize = filesize($output_info[$o]['file']);
              if ($real_filesize != $output_info[$o]['source_size']) {
                echo "Error downloading file, size doesn't match (" . $real_filesize . " != " . $output_info[$o]['source_size'] . ")!" . PHP_EOL;
              }else {
                $successfull_count++;
              }
            }
        }
        echo $successfull_count . '/' . $o . ' files downloaded successfully!' . PHP_EOL . PHP_EOL;
        curl_multi_close($multi_handle);
        // Unchanged files
        if($count_unchanged > 0) {
            echo 'Skipped ' . $count_unchanged . ' unchanged files' . PHP_EOL;
        }
        // Filtered files
        if($count_extension > 0) {
            echo 'Skipped ' . $count_extension . ' files due to wrong file extension'. PHP_EOL;
        }
    } else {
        echo "Failed to download repo content!" . PHP_EOL;
        echo "Repo content: " . $repo_content . PHP_EOL;
    }
}

echo "Finished pokemon image refresh." . PHP_EOL;
?>
