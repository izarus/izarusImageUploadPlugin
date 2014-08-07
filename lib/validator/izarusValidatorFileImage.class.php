<?php

class izarusValidatorFileImage extends sfValidatorFile {

/**
 * Configures the validator
 *
 * Available options:
 *
 *    max_height      Maximum height for the uploaded image
 *    max_width       Maximum width for the uploaded image
 *    max_width       Minimum width for the uploaded image
 *    max_height      Minimum height for the uploaded image
 *    ratio           Specific ratio requirement for the image. May be an array (width,height) ratio or 'square'
 *    thumbnails      Associative array of desired thumbnails for the image.
 *                    array(
 *                      width => (integer),
 *                      height => (integet),
 *                      mime => (string),       <<< 'image/jpeg',image/pjpeg' (default) ,'image/png','image/gif'
 *                      quality => (integer)    <<< 0-100 for image/jpeg (80 default)
 *                      option => (string)       <<< 'exact','auto','portrait','landscape','crop'
 *                    )
 *
 *
 *
 * @param  array  $options  Options for validator
 * @param  array  $messages Messages for validations
 */
  protected function configure($options = array(), $messages = array())
  {
    $this->addOption('max_height',null);
    $this->addOption('min_height',null);
    $this->addOption('max_width',null);
    $this->addOption('min_width',null);
    $this->addOption('ratio',null);
    $this->addOption('thumbnails', array());

    $this->addMessage('max_width', 'Uploaded image is too wide.');
    $this->addMessage('max_height', 'Uploaded image is too tall.');
    $this->addMessage('min_width', 'Uploaded image is not wide enough.');
    $this->addMessage('min_height', 'Uploaded image is not tall enough.');
    $this->addMessage('not_image', 'Uploaded file is not an image.');
    $this->addMessage('not_square', 'The image must be square.');
    $this->addMessage('invalid_image', '%value% is an incorrect image file.');

    if(isset($options['ratio']) && is_array($options['ratio'])){
      $this->addMessage('not_ratio', 'The image must be the correct dimensions ('.$options['ratio'][0].' x '.$options['ratio'][1].')');
    }

    parent::configure($options, $messages);

    $this->setOption('validated_file_class', 'izarusThumbnailsValidatedFile');
  }

  protected function doClean($value)
  {
    if (!is_array($value) || !isset($value['tmp_name'])){
      throw new sfValidatorError($this, 'invalid', array('value' => (string) $value));
    }

    if (!isset($value['name'])){
      $value['name'] = '';
    }

    if (!isset($value['error'])){
      $value['error'] = UPLOAD_ERR_OK;
    }

    if (!isset($value['size'])){
      $value['size'] = filesize($value['tmp_name']);
    }

    if (!isset($value['type'])){
      $value['type'] = 'application/octet-stream';
    }

    switch ($value['error']){
      case UPLOAD_ERR_INI_SIZE:
        $max = ini_get('upload_max_filesize');
        if ($this->getOption('max_size'))
        {
          $max = min($max, $this->getOption('max_size'));
        }
        throw new sfValidatorError($this, 'max_size', array('max_size' => $max, 'size' => (int) $value['size']));
      case UPLOAD_ERR_FORM_SIZE:
        throw new sfValidatorError($this, 'max_size', array('max_size' => 0, 'size' => (int) $value['size']));
      case UPLOAD_ERR_PARTIAL:
        throw new sfValidatorError($this, 'partial');
      case UPLOAD_ERR_NO_TMP_DIR:
        throw new sfValidatorError($this, 'no_tmp_dir');
      case UPLOAD_ERR_CANT_WRITE:
        throw new sfValidatorError($this, 'cant_write');
      case UPLOAD_ERR_EXTENSION:
        throw new sfValidatorError($this, 'extension');
    }

    // check file size
    if ($this->hasOption('max_size') && $this->getOption('max_size') < (int) $value['size']){
      throw new sfValidatorError($this, 'max_size', array('max_size' => $this->getOption('max_size'), 'size' => (int) $value['size']));
    }

    $mimeType = $this->getMimeType((string) $value['tmp_name'], (string) $value['type']);

    // check mime type
    if ($this->hasOption('mime_types')){
      $mimeTypes = is_array($this->getOption('mime_types')) ? $this->getOption('mime_types') : $this->getMimeTypesFromCategory($this->getOption('mime_types'));
      if (!in_array($mimeType, array_map('strtolower', $mimeTypes)))
      {
        throw new sfValidatorError($this, 'mime_types', array('mime_types' => $mimeTypes, 'mime_type' => $mimeType));
      }
    }

    $class = $this->getOption('validated_file_class');

    $clean = new $class($value['name'], $mimeType, $value['tmp_name'], $value['size'], $this->getOption('path'), $this->getOption('thumbnails'));

    $size = @getimagesize($clean->getTempName());

    if (!$size){
      throw new sfValidatorError($this, 'invalid_image', array('value' => $value['name']));
    }

    list($width, $height) = $size;

    if ($this->getOption('ratio')){
      $ratio = $this->getOption('ratio');

      if($ratio == 'square' && $width != $height) {
        throw new sfValidatorError($this, 'not_square', array('value' => (string) $value));
      } elseif(is_array($ratio) && $width / $height != $ratio[0] / $ratio[1]) {
        throw new sfValidatorError($this, 'not_ratio', array('value' => (string) $value));
      }
    }

    if($this->getOption('max_height') && $this->getOption('max_height') < $height){
        throw new sfValidatorError($this, 'max_height', array('value' => $value['name'], 'max_height' => $this->getOption('max_height')));
    }

    if($this->getOption('min_height') && $this->getOption('min_height') > $height){
        throw new sfValidatorError($this, 'min_height', array('value' => $value['name'], 'min_height' => $this->getOption('min_height')));
    }

    if($this->getOption('max_width') && $this->getOption('max_width') < $width){
        throw new sfValidatorError($this, 'max_width', array('value' => $value['name'], 'max_width' => $this->getOption('max_width')));
    }

    if($this->getOption('min_width') && $this->getOption('min_width') > $width){
        throw new sfValidatorError($this, 'min_width', array('value' => $value['name'], 'min_width' => $this->getOption('min_width')));
    }

    return $clean;
  }
}