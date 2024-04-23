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
        $files = [];

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

    public static function getFileFromBucket(?string $bucket, string $fileId, ?int $width, ?int $height): mixed {
        global $db, $config;

        if(isset($bucket)) {
            $db->query("SELECT * FROM exm_storage_bucket WHERE id = ? AND bucket_id=?", $fileId, $bucket); 
        } else {
            $db->query("SELECT * FROM exm_storage_bucket WHERE id = ?", $fileId);
        }

        if($db->hasRows()) {
            $row = $db->getRow();

            $mime = explode('/', $row->mime_type);
            $extension = $mime[1];
            $extension = str_replace('jpeg', 'jpg', $extension);

            $filePath = $config->get('storage_dir') . '/' . $row->id . '.' . $extension;

            if(file_exists($filePath)) { 
                header('Content-Type: ' . $row->mime_type);
                if(Image::isImage($filePath) && (isset($width) || isset($height))) {
                    Image::resizeImage($filePath, $width ?? null, $height ?? null);
                } else {
                    echo file_get_contents($filePath);
                }
                return $row;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
?>
