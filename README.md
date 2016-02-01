izarusImageUploadPlugin
=======================

Plugin Symfony 1.4 para subir imágenes mediante los formularios y generar miniaturas automáticamente.

#### Version
* 0.3

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
          'option' => 'crop',
          'quality' => 100,
        ),
        'footer-thumb' => array(
          'width' => 50,
          'height' => 50,
          'mime' => 'image/png',
          'option' => 'fill',
        )),
      ),array(
        'min_width' => 'La imagen debe ser cuadrada de al menos 180 pixeles de ancho.',
        'min_height' => 'La imagen debe ser cuadrada de al menos 180 pixeles de alto.',
        'not_square' => 'Debe subir una imagen cuadrada.',
        'not_image' => 'Debe subir solo imágenes.',
        'max_size' => 'Debe subir una imagen de no más de 2MB',
      ));
```

Options available:

exact: Creates a thumnail of exact width and height, not keeping proportions.
auto: (default) Class decides to keep the width and adjust the height or keep the height and adjust the width, or keep a square.
portrait: Keep the height given and auto adjust width.
landscape: Keep the width given for the thumbnail and auto adjust height.
crop: Generates a thumbnail of exact width and height, keeping proportions and cropping extra areas to fit.
fill: Generates a thumbnail of exact width and height, keeping proportions and filling with transparency/white extra areas to fit. If mime is 'image/png', it fills with transparent color, else, fills with white color.

## Changelog

0.3 Fill option
0.2 Only GD, Crop option
0.1 Create thumbnails
