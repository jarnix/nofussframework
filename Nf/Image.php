<?php
namespace Nf;

abstract class Image
{

    public static function generateThumbnail($imagePath, $thumbnailPath, $thumbnailWidth = 100, $thumbnailHeight = 100, $cut = false, $border = false)
    {

        // load the original image
        $image = new \Imagick($imagePath);

        // undocumented method to limit imagick to one cpu thread
        $image->setResourceLimit(6, 1);

        // get the original dimensions
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $r = $width / $height;

        // the image will not be cut and the final dimensions will be within the requested dimensions
        if (! $cut) {
            // width & height : maximums and aspect ratio is maintained
            if ($thumbnailHeight == 0) {
                $thumbnailHeight = ceil($thumbnailWidth / $r);
                if (! ($thumbnailWidth == 0 && $thumbnailHeight == 0)) {
                    // create the thumbnail
                    $image->thumbnailImage($thumbnailWidth, $thumbnailHeight);
                } else {
                    throw new \Exception('Cannot create a 0x0 thumbnail');
                }
            } elseif ($thumbnailWidth == 0) {
                $thumbnailWidth = ceil($thumbnailHeight * $r);
                if (! ($thumbnailWidth == 0 && $thumbnailHeight == 0)) {
                    // create thumbnail
                    $image->thumbnailImage($thumbnailWidth, $thumbnailHeight);
                } else {
                    throw new \Exception('Cannot create a 0x0 thumbnail');
                }
            } else {
                // determine which dimension to fit to
                $fitWidth = ($thumbnailWidth / $width) < ($thumbnailHeight / $height);
                // create thumbnail
                $image->thumbnailImage($fitWidth ? $thumbnailWidth : 0, $fitWidth ? 0 : $thumbnailHeight, $border ? false : true, true);
            }

            if ($border) {
                $image->borderImage('white', ($thumbnailWidth - $image->getImageWidth()) / 2, ($thumbnailHeight - $image->getImageHeight()) / 2);
            }
        } else {
            if ($thumbnailWidth == 0 || $thumbnailHeight == 0) {
                throw new \Exception('Cannot generate thumbnail in "cut" mode when a dimension equals zero. Specify the dimensions.');
            }

            // scale along the smallest side
            if ($r < 1) {
                $newWidth = $thumbnailWidth;
                $newHeight = ceil($thumbnailWidth / $r);
            } else {
                $newWidth = ceil($thumbnailHeight * $r);
                $newHeight = $thumbnailHeight;
            }

            // if the requested thumbnail is too large
            if ($newWidth < $thumbnailWidth || $newHeight < $thumbnailHeight) {
                if ($newWidth < $thumbnailWidth) {
                    $newWidth = $thumbnailWidth;
                    $newHeight = ceil($thumbnailWidth / $r);
                }
                if ($newHeight < $thumbnailHeight) {
                    $newHeight = $thumbnailHeight;
                    $newWidth = ceil($thumbnailHeight * $r);
                }
            }

            $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_CATROM, 1);

            $width = $newWidth;
            $height = $newHeight;

            $workingImage = $image->getImage();
            $workingImage->contrastImage(50);
            $workingImage->setImageBias(10000);
            $kernel = array(
                0,
                - 1,
                0,
                - 1,
                4,
                - 1,
                0,
                - 1,
                0
            );

            $workingImage->convolveImage($kernel);

            $x = 0;
            $y = 0;
            $sliceLength = 16;

            while ($width - $x > $thumbnailWidth) {
                $sliceWidth = min($sliceLength, $width - $x - $thumbnailWidth);
                $imageCopy1 = $workingImage->getImage();
                $imageCopy2 = $workingImage->getImage();
                $imageCopy1->cropImage($sliceWidth, $height, $x, 0);
                $imageCopy2->cropImage($sliceWidth, $height, $width - $sliceWidth, 0);

                if (self::entropy($imageCopy1) < self::entropy($imageCopy2)) {
                    $x += $sliceWidth;
                } else {
                    $width -= $sliceWidth;
                }
            }

            while ($height - $y > $thumbnailHeight) {
                $sliceHeight = min($sliceLength, $height - $y - $thumbnailHeight);
                $imageCopy1 = $workingImage->getImage();
                $imageCopy2 = $workingImage->getImage();
                $imageCopy1->cropImage($width, $sliceHeight, 0, $y);
                $imageCopy2->cropImage($width, $sliceHeight, 0, $height - $sliceHeight);

                if (self::entropy($imageCopy1) < self::entropy($imageCopy2)) {
                    $y += $sliceHeight;
                } else {
                    $height -= $sliceHeight;
                }
            }

            $image->cropImage($thumbnailWidth, $thumbnailHeight, $x, $y);
        }

        if ($thumbnailPath != null) {
            $image->writeImage($thumbnailPath);
            $image->clear();
            $image->destroy();
            return $thumbnailPath;
        } else {
            return $image;
        }
    }

    private static function entropy($image)
    {
        $image->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
        $pixels = $image->getImageHistogram();
        $hist = array();
        foreach ($pixels as $p) {
            $color = $p->getColor();
            $theColor = $color['r'];
            if (! isset($hist[$theColor])) {
                $hist[$theColor] = 0;
            }
            $hist[$theColor] += $p->getColorCount();
        }
        // calculate the entropy from the histogram
        // cf http://www.mathworks.com/help/toolbox/images/ref/entropy.html
        $entropy = 0;
        foreach ($hist as $c => $v) {
            $entropy -= $v * log($v, 2);
        }
        return $entropy;
    }

    public static function identifyImage($sourceFile)
    {
        return \Imagick::identifyImage($sourceFile);
    }
}
