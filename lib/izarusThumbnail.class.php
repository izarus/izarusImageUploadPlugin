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
    // echo "<hr><pre>Files: ";
    // var_dump($filename);
    // echo "</pre>";

    // echo "<hr><pre>SOURCE: ";
    // var_dump($source);
    // echo "</pre>";
    // die(' izarusThumbnail.class');
    if (!is_readable($source))
    {
      throw new Exception(sprintf('The file "%s" is not readable.', $source));
    }

    $imgData = @GetImageSize($source);
    // echo "<hr><pre>IMGDATA: ";
    // var_dump($imgData);
    // echo "</pre>";
    // die(' izarusThumbnail.class');
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

      // echo "<hr><pre> LOADER: ";
      // var_dump($this->source);
      // var_dump($this->sourceWidth);
      // var_dump($this->sourceHeight);
      // var_dump($this->sourceMime);
      // echo "</pre>";
      // die(' izarusThumbnail.class');

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
          imagecopyresampled($imageResized, $this->source, 0, 0, 0, 0, $optimalWidth, $optimalHeight , $this->sourceWidth, $this->sourceHeight);

          $this->source = $imageResized;
          $this->sourceWidth = $optimalWidth;
          $this->sourceHeight = $optimalHeight;
          $this->sourceX = ($optimalWidth - $this->thumbnailWidth) / 2;
          $this->sourceY = ($optimalHeight - $this->thumbnailHeight) / 2;

          break;

        case 'fill':

          $white = imagecreatetruecolor($this->sourceWidth, $this->sourceHeight);
          // Fill the new image with white background
          $bg = imagecolorallocate($white, 255, 255, 255);
          imagefill($white, 0, 0, $bg);
          imagecopy($white, $this->source, 0, 0, 0, 0, $this->sourceWidth, $this->sourceHeight);

          $filename = $white;

          $ancho = imagesx($filename);
          $alto = imagesy($filename);

          $Nancho=$this->thumbnailWidth;
          $Nalto=$this->thumbnailHeight;

          $p=min($ancho,$alto);
          $q=max($ancho,$alto);

          $redim_ancho = $Nancho;
          $redim_alto = $Nalto;
          $redim_x = 0;
          $redim_y = 0;

          $thumb = imagecreatetruecolor($Nancho,$Nalto);
          $color = imagecolorallocate($thumb, 255, 255, 255);
          imagefill($thumb, 0, 0, $color);

          if($this->thumbnailWidth == $this->thumbnailHeight){
            if($ancho > $alto){
              $redim_ancho = $ancho * ($redim_alto / $alto);
              $redim_alto = $Nalto;

              $redim_x = ($Nancho - $redim_ancho) / 2;
              if($redim_x<0) $redim_x = -1 * $redim_x;
            }else{
              $redim_ancho = $Nancho;
              $redim_alto = $alto * ($redim_ancho / $ancho);

              $redim_y = ($Nalto - $redim_alto) / 2;
              if($redim_y<0) $redim_y = -1 * $redim_y;
            }

            $resized = imagecreatetruecolor($redim_ancho,$redim_alto);

            //imagecopyresampled($thumb,$filename,0,0,$x,$y,$Nancho,$Nalto,$q,$q);
            imagecopyresampled($resized,$filename,0,0,0,0,$Nancho,$Nalto,$q,$q);
            imagecopy($thumb,$resized,$redim_x,$redim_y,0,0,$redim_ancho,$redim_alto);
          }else{

            if($alto>=$ancho){

              $a=($w*$alto)/$ancho;
              $b=($h*$alto)/$a;

              if($b>$alto){
                $r=$alto/$b;
                $ancho=$ancho*$r;
                $b=$alto;
              }else{
                $y=($alto-$b)/2;
              }

              imagecopyresampled($thumb,$filename,0,0,$this->thumbnailWidth,$this->thumbnailHeight,$Nancho,$Nalto,$ancho,$b);
            }else{
              $a=($this->thumbnailHeight*$ancho)/$alto;
              $b=($this->thumbnailWidth*$ancho)/$a;

              imagecopyresampled($thumb,$filename,0,0,$this->thumbnailWidth,$this->thumbnailHeight,$Nancho,$Nalto,$b,$alto);
            }

          }
          $thumb = $this->imagetranstowhite($thumb);
          break;
      }

      $this->thumb = imagecreatetruecolor($this->thumbnailWidth, $this->thumbnailHeight);

      if ($this->option == 'crop'){
        imagecopy($this->thumb, $this->source, 0, 0, $this->sourceX, $this->sourceY, $this->thumbnailWidth, $this->thumbnailHeight);
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

  /**
 * [imagetranstowhite description]
 * @param  [type] $trans [description]
 * @return [type]        [description]
 */
  function imagetranstowhite($trans) {
    // Create a new true color image with the same size
    $w = imagesx($trans);
    $h = imagesy($trans);
    $white = imagecreatetruecolor($w, $h);

    // Fill the new image with white background
    $bg = imagecolorallocate($white, 255, 255, 255);
    imagefill($white, 0, 0, $bg);

    // Copy original transparent image onto the new image
    imagecopy($white, $trans, 0, 0, 0, 0, $w, $h);
    return $white;
  }

  function ext($fichero) {
    $fichero = strtolower($fichero) ;
    $extension = explode(".", $fichero) ;
    $n = count($extension)-1;
    $extension = $extension[$n];
    return $extension;
  }

}
