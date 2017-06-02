# custom-crop

## Adding size

```php
add_filter('custom_crop_sizes', 'current_site_crop_sizes');

function current_site_crop_sizes ($sizes) {
    $sizes = array_merge($size, array(
        'custom600x400' => array(__('Review Thumb 600x400'), 600, 400),
        'example' => array(__('Example'), 200, 150),
    ));

    return $sizes;
}
```

## Getting crops

```php
if(has_post_thumbnail()) {
    $src = wp_get_attachment_image_src(get_post_thumbnail_id(), 'custom600x400');
    $url = $src[0];
    $width = $src[1];
    $height = $src[2];
    $is_intermediate = $src[3];
    
    if ($is_intermediate) {
        //crop exists
    } else {
        //$url is full sized image
    }
}
```

## Data structure

```javascript

var attachment = {
    attachment_id: 40,
    width: 888,
    height: 888
};

```

 
## ToDo

 * Size file deleting
 * After generate crop action, to generate some thing from generated.
 * Gernerate a images with custom dynamic content witch is visible in editor