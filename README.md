
# Wp Breadcrumb 

A PHP class to generate seo friendly breadcrumbs in WordPress Themes



## Support

- Page
- Post
- Custom Post Type
- Category term archive
- Tag term archive
- Custom Taxonomy archive 
- Author archive
- Date archive
- Search results page
- Pagination
- 404
- Accessibility
## Usage/Examples

### Create an Instance
To view the breadcrumbs you need to create an instance of Wp_Breadcrumb as follows:

```php
<?php
    
    require_once 'class-wp-breadcrumb.php';

    $breadcrumb = new Wp_Breadcrumb();
    $breadcrumb->display();

?>
```

### Create an Instance with custom separetor and css class

```php
<?php

    require_once 'class-wp-breadcrumb.php';

    $breadcrumb = new Wp_Breadcrumb('>', 'breadcrumb', 'breadcrumb_item');
    $breadcrumb->display();

?>
```
## Authors

- [@Giulio Delmasto](https://www.github.com/giuliodelmastro)
