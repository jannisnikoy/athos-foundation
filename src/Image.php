<?php

namespace Athos\Foundation;

/**
* Image
* Basic Image resizing
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Image {
    public static function isImage($fileName): bool {
        $type = exif_imagetype($fileName);
        $supportedTypes = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG];
        
        return in_array($type, $supportedTypes);
    }

    public static function resizeImage($originalFile, $newWidth = null, $newHeight = null): bool {
        $type = exif_imagetype($originalFile);

        switch ($type) {
            case IMAGETYPE_GIF:
                $srcImg = imagecreatefromgif($originalFile);
                break;
            case IMAGETYPE_JPEG:
                $srcImg = imagecreatefromjpeg($originalFile);
                break;
            case IMAGETYPE_PNG:
                $srcImg = imagecreatefrompng($originalFile);
                break;
        }
        
        $width = imagesx($srcImg);
        $height = imagesy($srcImg);
        
        if (isset($newWidth) && $newWidth > 0) {
            $ratio = $width / $newWidth;
            $newHeight = $height / $ratio;
        } elseif (isset($newHeight) && $newHeight > 0) {
            $ratio = $height / $newHeight;
            $newWidth = $width / $ratio;
        } else {
            return false; 
        }
        
        $newImg = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {
            imagecolortransparent(
                $newImg,
                imagecolorallocatealpha($newImg, 0, 0, 0, 127)
            );

            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
        }
        
        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        
        switch ($type) {
            case IMAGETYPE_GIF:
                imagegif($newImg);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($newImg, null, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImg);
                break;
        }
        
        imagedestroy($srcImg);
        imagedestroy($newImg);

        return true;
    }
}
?>