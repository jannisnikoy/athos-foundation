<?php

namespace Athos\Foundation;

/**
* Files
* Load files
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Files {
    /**
    * Retrieves a list of files within the provided directory.
    *
    * @param string $directory Directory to search
    * @param string $extension Optional requirement of a specific file extension
    * @return array Array of filenames
    */
    public static function getFilesInDirectory(string $directory, string $extension = ''): array {
        $files = array();

        if (is_dir($directory) && $handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($extension != '') {
                    if (strlen($file) > 2 && substr($file, strlen($file) - strlen($extension), strlen($extension)) == $extension) {
                        $files[] = $file;
                    }
                } else if(strlen($file) > 2) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
?>
