izarusImageUploadPlugin
=======================

Plugin Symfony 1.4 para subir imágenes mediante los formularios y generar miniaturas automáticamente.

#### Version
* 0.2

#### Requiere
* PHP GD

#### Incluye
* `izarusThumbnail.class.php` Crea los thumbnails.
* `izarusValidatorFileImage.class.php` Validator Symfony 1.4 para utilizar este plugin
* `izarusThumbnailsValidatedFile.class.php` Valida el archivo de imagen a subir y genera los thumbnails

## Modo de uso

Un ejemplo de uso del validador para subir una imagen y crear un par de thumbnails.

```php
   $this->validatorSchema['imagen'] = new izarusValidatorFileImage(array(
      'path' => sfConfig::get('sf_upload_dir').'/myimages/',
      'required' => false,
      'min_width' => 200,
      'min_height' => 200,
      'max_size' => 2500000,
      'ratio' => 'square',
      'thumbnails' => array(
        'thumb' => array(
          'width' => 150,
          'height' => 150,
        ),
        'home-thumb' => array(
          'width' => 400,
          'height' => 400,
          'mime' => 'image/jpeg',
          'quality' => 100,
        ),
        'footer-thumb' => array(
          'width' => 50,
          'height' => 50,
          'mime' => 'image/png',
        )),
      ),array(
        'min_width' => 'La imagen debe ser cuadrada de al menos 180 pixeles de ancho.',
        'min_height' => 'La imagen debe ser cuadrada de al menos 180 pixeles de alto.',
        'not_square' => 'Debe subir una imagen cuadrada.',
        'not_image' => 'Debe subir solo imágenes.',
        'max_size' => 'Debe subir una imagen de no más de 2MB',
      ));
```


