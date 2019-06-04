<?php
require_once 'init.inc.php';

class ApplicationVersion
{
    const MAJOR = 1;
    const MINOR = 2;
    const PATCH = 3;

    public static function get()
    {
        $commitHash = trim(exec('git log --pretty="%h" -n1 HEAD', $out, $failed)); // Example to see if command was executed

        if($failed){
            echo 'Execution failed!';
            return false;
        }else{
            dump($failed);
            dump($out);
            dump($commitHash);
            
            $commitDate = new \DateTime(trim(exec('git log -n1 --pretty=%ci HEAD')));
            $commitDate->setTimezone(new \DateTimeZone('UTC'));
            
            return sprintf('v%s.%s.%s-dev.%s (%s)', self::MAJOR, self::MINOR, self::PATCH, $commitHash, $commitDate->format('Y-m-d H:i:s'));
        }

    }
}

echo 'MyApplication ' . ApplicationVersion::get();

// MyApplication v1.2.3-dev.b576fd7 (2016-11-02 14:11:22)