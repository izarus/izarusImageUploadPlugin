<?php
/**
 * izarusThumbnail
 * (c) Izarus - http://www.izarus.cl
 *
 * Author: David Vega
 *
 * With code of many scripts on the internet.
 *
 * Generate thumbnail image from a uploaded one.
 * It needs an image, and the dimensions.
 *
 * Required PHP GD Library
 */
class izarusThumbnail
{
  protected
    $option,
    $sourceWidth,
    $sourceHeight,
    $sourceMime,
    $thumbnailWidth,
    $thumbnailHeight,
    $crop,
    $sourceX = 0,
    $sourceY = 0,
    $thumbnailX = 0,
    $thumbnailY = 0,
    $source,
    $thumb;

  /**
   * List of accepted image types based on MIME
   * descriptions that this adapter supports
   */
  protected $imgTypes = array(
    'image/jpeg',
    'image/pjpeg',
    'image/png',
    'image/gif',
  );

  /**
   * Stores function names for each image type
   */
  protected $imgLoaders = array(
    'image/jpeg'  => 'imagecreatefromjpeg',
    'image/pjpeg' => 'imagecreatefromjpeg',
    'image/png'   => 'imagecreatefrompng',
    'image/gif'   => 'imagecreatefromgif',
  );

  /**
   * Stores function names for each image type
   */
  protected $imgCreators = array(
    'image/jpeg'  => 'imagejpeg',
    'image/pjpeg' => 'imagejpeg',
    'image/png'   => 'imagepng',
    'image/gif'   => 'imagegif',
  );

  /**
   * Constructor
   *
   * Options available:
   *
   *    exact       Creates a thumnail of exact width and height, not keeping proportions.
   *    auto        (default) Class decides to keep the width and adjust the height or keep
   *                the height and adjust the width, or keep a square.
   *    portrait    Keep the height given and auto adjust width.
   *    landscape   Keep the width given for the thumbnail and auto adjust height.
   *    crop        Generates a thumbnail of exact width and height, keeping proportions and cropping extra areas to fit.
   *    fill        Generates a thumbnail of exact width and height, keeping proportions and filling with transparency/white extra areas to fit.
   *
   * @param integer $thumbnailWidth  Thumbnail max width
   * @param integer $thumbnailHeight Thumbnail max height
   * @param string  $option          Option for create the thumbnail
   */
  public function __construct($thumbnailWidth = 150, $thumbnailHeight = 150, $option = 'auto')
  {
    $this->option = $option;
    $this->thumbnailWidth = $thumbnailWidth;
    $this->thumbnailHeight = $thumbnailHeight;
  }

  /**
   * Load the image and generate the thumnail.
   * @param  string $source File path (uploaded or existent)
   * @return boolean        Returns true. Otherwise, exceptions may be thrown.
   */
  public function loadFile($source)
  {
    if (!is_readable($source))
    {
      throw new Exception(sprintf('The file "%s" is not readable.', $source));
    }

    $imgData = @GetImageSize($source);

    if (!$imgData)
    {
      throw new Exception(sprintf('Could not load image %s', $source));
    }

    if (in_array($imgData['mime'], $this->imgTypes))
    {

      $loader = $this->imgLoaders[$imgData['mime']];

      if(!function_exists($loader))
      {
        throw new Exception(sprintf('Function %s not available. Please enable the GD extension.', $loader));
      }

      $this->source = $loader($source);
      $this->sourceWidth = $imgData[0];
      $this->sourceHeight = $imgData[1];
      $this->sourceMime = $imgData['mime'];

      $this->thumb = imagecreatetruecolor($this->thumbnailWidth, $this->thumbnailHeight);
      if ($this->sourceMime == 'image/png') {
        imagealphablending($this->thumb,false);
        imagesavealpha($this->thumb,true);
        $transparent = imagecolorallocatealpha($this->thumb, 255, 255, 255, 127);
        imagefilledrectangle($this->thumb, 0, 0, $this->thumbnailWidth, $this->thumbnailHeight, $transparent);
      } else {
        $color = imagecolorallocate($this->thumb, 255, 255, 255);
        imagefill($this->thumb, 0, 0, $color);
      }

      switch ($this->option)
      {
        case 'exact':
          break;
        case 'portrait':
          $this->thumbnailWidth = $this->thumbnailHeight * ($this->sourceWidth/$this->sourceHeight);
          break;
        case 'landscape':
          $this->thumbnailHeight = $this->thumbnailWidth * ($this->sourceHeight/$this->sourceWidth);
          break;
        case 'auto':
          if ($this->sourceHeight < $this->sourceWidth) // *** Image to be resized is wider (landscape)
          {
            $this->thumbnailHeight = $this->thumbnailWidth * ($this->sourceHeight/$this->sourceWidth);
          }
          elseif ($this->sourceHeight > $this->sourceWidth) // *** Image to be resized is taller (portrait)
          {
            $this->thumbnailWidth = $this->thumbnailHeight * ($this->sourceWidth/$this->sourceHeight);
          }
          else // *** Image is a square
          {
            if ($this->thumbnailHeight < $this->thumbnailWidth) {
              $this->thumbnailHeight = $this->thumbnailWidth * ($this->sourceWidth/$this->sourceHeight);
            } else if ($this->thumbnailHeight > $this->thumbnailWidth) {
              $this->thumbnailWidth = $this->thumbnailHeight * ($this->sourceWidth/$this->sourceHeight);
            } else {
              // *** Square being resized to a square
            }
          }
          break;
        case 'crop':
          $originalRatio = $this->sourceWidth / $this->sourceHeight;
          $thumbnailRatio = $this->thumbnailWidth / $this->thumbnailHeight;

          if ($originalRatio > $thumbnailRatio)
          {
            $optimalWidth = (int) ($this->thumbnailHeight * $originalRatio);
            $optimalHeight = $this->thumbnailHeight;
          }
          else
          {
            $optimalWidth = $this->thumbnailWidth;
            $optimalHeight = (int) ($this->thumbnailWidth / $originalRatio);
          }

          $imageResized = imagecreatetruecolor($optimalWidth , $optimalHeight);
          if ($this->sourceMime == 'image/png') {
            imagealphablending($imageResized,false);
            imagesavealpha($imageResized,true);
            $transparent = imagecolorallocatealpha($imageResized, 255, 255, 255, 127);
            imagefilledrectangle($imageResized, 0, 0, $this->thumbnailWidth, $this->thumbnailHeight, $transparent);
          }
          imagecopyresampled($imageResized, $this->source, 0, 0, 0, 0, $optimalWidth, $optimalHeight , $this->sourceWidth, $this->sourceHeight);

          $this->source = $imageResized;
          $this->sourceWidth = $optimalWidth;
          $this->sourceHeight = $optimalHeight;
          $this->sourceX = ($optimalWidth - $this->thumbnailWidth) / 2;
          $this->sourceY = ($optimalHeight - $this->thumbnailHeight) / 2;

          break;

        case 'fill':

          if($this->thumbnailWidth == $this->thumbnailHeight) {
            // SQUARE THUMB
            if($this->sourceWidth > $this->sourceHeight){
              // LANDSCAPE SOURCE
              $new_width = $this->thumbnailWidth;
              $new_height = $this->sourceHeight * $this->thumbnailWidth / $this->sourceWidth;
              $pos_x = 0;
              $pos_y = floor( ($this->thumbnailHeight-$new_height) / 2 );
            }elseif($this->sourceWidth < $this->sourceHeight){
              // PORTRAIT SOURCE
              $new_width = $this->sourceWidth * $this->thumbnailHeight / $this->sourceHeight;
              $new_height = $this->thumbnailHeight;
              $pos_x = floor( ($this->thumbnailWidth-$new_width) / 2 );
              $pos_y = 0;
            } else {
              // SQUARE SOURCE
              $new_width = $this->thumbnailWidth;
              $new_height = $this->thumbnailHeight;
              $pos_x = 0;
              $pos_y = 0;
            }

          } else {
            // NON SQUARE THUMB

            $ratio_source = $this->sourceWidth/$this->sourceHeight;
            $ratio_thumb = $this->thumbnailWidth/$this->thumbnailHeight;

            if ($ratio_source >= $ratio_thumb) {
              $new_width = $this->thumbnailWidth;
              $new_height = $this->sourceHeight * $this->thumbnailWidth / $this->sourceWidth;
              $pos_x = 0;
              $pos_y = floor( ($this->thumbnailHeight-$new_height) / 2 );
            } else {
              $new_width = $this->sourceWidth * $this->thumbnailHeight / $this->sourceHeight;
              $new_height = $this->thumbnailHeight;
              $pos_x = floor( ($this->thumbnailWidth-$new_width) / 2 );
              $pos_y = 0;
            }

          }

          $resized = imagecreatetruecolor($new_width,$new_height);
          if ($this->sourceMime == 'image/png') {
            imagealphablending($resized,false);
            imagesavealpha($resized,true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $this->thumbnailWidth, $this->thumbnailHeight, $transparent);
          }
          imagecopyresampled($resized,$this->source,0,0,0,0,$new_width,$new_height,$this->sourceWidth,$this->sourceHeight);
          break;
      }

      if ($this->option == 'crop'){
        imagecopy($this->thumb, $this->source, 0, 0, $this->sourceX, $this->sourceY, $this->thumbnailWidth, $this->thumbnailHeight);
      } elseif($this->option == 'fill') {
        imagecopy($this->thumb,$resized,$pos_x,$pos_y,0,0,$new_width,$new_height);
      } else {
        imagecopyresampled($this->thumb, $this->source, $this->thumbnailX, $this->thumbnailY, $this->sourceX, $this->sourceY, $this->thumbnailWidth, $this->thumbnailHeight, $this->sourceWidth, $this->sourceHeight);
      }

      return true;
    }
    else
    {
      throw new Exception(sprintf('Image MIME type %s not supported', $imgData['mime']));
    }
  }

  /**
   * Saves the created thumbnail in the given path.
   * @param  string  $path          Path to save the thumbnail
   * @param  string  $targetMime    MIME type of the thumbnail (See imgCreators property)
   * @param  integer $targetQuality Image quality for JPG (0-100)
   */
  public function save($path, $targetMime = null, $targetQuality = 80)
  {
    if ($targetMime && !(in_array($targetMime, $this->imgTypes))) {
      throw new Exception(sprintf("Image MIME type %s is not supported to save the thumbnail", $targetMime));
    }

    if($targetMime)
    {
      $creator = $this->imgCreators[$targetMime];
    }
    else
    {
      $creator = $this->imgCreators[$this->sourceMime];
    }

    if ($creator == 'imagejpeg')
    {
      imagejpeg($this->thumb, $path, $targetQuality);
    }
    else
    {
      $creator($this->thumb, $path);
    }
  }

  /**
   * Free source memory
   */
  public function freeSource()
  {
    if (is_resource($this->source))
    {
      imagedestroy($this->source);
    }
  }

  /**
   * Free thumbnail memory
   */
  public function freeThumb()
  {
    if (is_resource($this->thumb))
    {
      imagedestroy($this->thumb);
    }
  }

  /**
   * Free thumbnail and source memory
   */
  public function freeAll()
  {
    $this->freeSource();
    $this->freeThumb();
  }

  /**
   * Triggers the free functions.
   */
  public function __destruct()
  {
    $this->freeAll();
  }

}
