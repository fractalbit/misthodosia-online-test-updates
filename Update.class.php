<?php
require_once 'init.inc.php';
require_once 'Parsedown.php';

class App {

    private $version_tag = '2.2.0';

    private $releases = array();
    private $tags = array();
    private $commits = array();

    private $repo_raw_data = array();
    
    public function get_raw_data(){       
        $repo = new Github();
        // I don't think we have to download the commits
        // We will work with tags for version checking and releases if there is one for that tag (for better changelong)
        // $this->repo_raw_data['commits'] = json_decode($repo->get('commits'), true); // Set second parameter to true to return an assosiative array instead of an object
        $this->repo_raw_data['tags'] = json_decode($repo->get('tags'), true); 
        $this->repo_raw_data['releases'] = json_decode($repo->get('releases'), true);
        
        $Parsedown = new Parsedown();        
        $md = '## ' . $this->repo_raw_data['releases'][0]['tag_name'] . "\n" . $this->repo_raw_data['releases'][0]['body'];
        echo $Parsedown->text($md);
        
        dump($this->repo_raw_data['releases']);
        dump($this->repo_raw_data['tags']);
        // dump($this->repo_raw_data['commits']);

        $this->save_data_by_sha();
        dump($this->commits);
        // $this->get_release_by_sha('a4f9f749f92b99a8212d38670110a303f3701281');
    }

    private function get_release_by_sha($commit_hash){
        if(!empty($commit_hash)){ // Should also check for validity
            $tag = array_search($commit_hash, $this->repo_raw_data['tags']);
            if($tag) {
                echo $tag;
            }
        }else{
            echo 'Error: Hash not provided';
        }
    }

    private function save_data_by_sha(){
        // Store just the data we want indexed by sha hash
        $releases = $this->repo_raw_data['releases'];
        $tags = $this->repo_raw_data['tags'];
        $commits = $this->repo_raw_data['commits'];

        foreach($commits as $cdata){
            $sha = $cdata['sha'];
            $this->commits[$sha] = array('message' => $cdata['commit']['message'], 'date' => $cdata['commit']['committer']['date']);
            echo App::convert_date($cdata['commit']['committer']['date']) . '<br />';
        }
    }

    public static function convert_date($ISO_8601){
        $date = new DateTime($ISO_8601);
        return $date->format("d/m/Y H:i");
    }

    
}

class Github {
    private $repo_url;    

    public function __construct($url = 'https://api.github.com/repos/fractalbit/misthodosia-online/'){
        $this->repo_url = $url;
    }

    public function get($query = 'commits'){
        $ch = curl_init();

        $fetch_url = $this->repo_url . $query;
        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $fetch_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);               
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/' . curl_version()['version']); // Απαραίτητο να στείλουμε έναν useragent στο github api για να δουλέψει

        $result = curl_exec($ch);
        curl_close($ch);
        
        if($result === FALSE) {
            echo "Error: " . curl_error($ch);
        } else {            
            return $result;
        }
    }

    public function download($tag){
        // Download the specified tag version from github
    }

    public function unzip(){
        // Just try the unzip for now with hardcoded file and path
        $zip = new ZipArchive;
        $res = $zip->open('./temp-experiments/misthodosia-online-master.zip');
        if ($res === TRUE) {
            $zip->extractTo('./temp-experiments/new_repo/');
            $zip->close();
            echo 'woot!';

            // Now copy the contents of misthodosia-online-master
            $orig = './temp-experiments/new_repo/misthodosia-online-master/';
            $target = './temp-experiments/new_repo/';

           xcopy($orig, $target);
        } else {
            echo 'doh!';   
        }
    }


}

/**
 * Copy a file, or recursively copy a folder and its contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       int      $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }

    // Clean up
    $dir->close();
    return true;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

<?php

// $mo = new App;
// $mo->get_raw_data();

$repo = new Github();
$repo->unzip();

// Just a quick test about versioning and version comparison
// $app_versions = array('v2.2.0', 'v2.2.1', 'v2.3', 'v2.3.1', 'v2.3.1', 'v2.3.0.9');
// $prev = '';
// foreach($app_versions as $key => $ver){
//     if(check_newer($prev, $ver)){
//         echo $prev . ' VS ' . $ver . ' : There is a new version, please upgrade<br>';
//     }else{
//         echo $prev . ' VS ' . $ver . ' : Your version is up to date<br>';
//     }
//     $prev = $ver;
// }

// function check_newer($current_version, $server_version){
//     if(version_compare($current_version, $server_version) < 0){
//         return true;
//     }else{
//         return false;
//     }
// }

?>

    
</body>
</html>