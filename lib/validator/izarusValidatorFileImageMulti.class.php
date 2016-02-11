<?php

class izarusValidatorFileImageMulti extends izarusValidatorFileImage
{

  protected function doClean($value)
  {
    $clean = array();

    foreach ($value as $file)
    {
      $clean[] = parent::doClean($file);
    }

    return $clean;
  }
}
