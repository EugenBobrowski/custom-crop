<?php

/*
Plugin Name: Custom  Crop
Plugin URI: http://wordpress.org/plugins/antispam/
Description:
Author: MRKS
Version: 1.0
*/

define('CUSTOM_CROP_VERSION', (WP_DEBUG) ? time() : '1.0');

class Custom_Crop
{

    protected static $instance;
    public $sizes;

    private function __construct()
    {
        add_action('load-page.php', array($this, 'init'));
        add_action('load-page-new.php', array($this, 'init'));
        add_action('load-post.php', array($this, 'init'));
        add_action('load-post-new.php', array($this, 'init'));
        add_filter('admin_post_thumbnail_html', array($this, 'add_featured_image_display_settings'), 10, 3);
        add_action('after_setup_theme', array($this, 'add_image_size'));
        add_action('wp_ajax_custom_crop', array($this, 'ajax_done'));
        add_filter('wp_generate_attachment_metadata', array($this, 'save_cropped_sizes'), 10, 2);
    }

    public function init()
    {

        add_action('admin_enqueue_scripts', array($this, 'assets'));
//        add_action('admin_footer', array($this, 'inline_views'));

    }

    public function assets()
    {
        wp_enqueue_media();

        wp_enqueue_script('custom-crop', plugin_dir_url(__FILE__) . 'js/custom-crop.js', array(
            'jquery',
            'jquery-ui-slider',
            'jquery-ui-draggable',), CUSTOM_CROP_VERSION);
        wp_localize_script('custom-crop', 'custom_crop_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            '_wpnonce' => wp_create_nonce('custom_crop'),
            'action' => 'custom_crop'

        ));


        wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.css');
        wp_enqueue_style('custom-crop', plugin_dir_url(__FILE__) . 'css/style.css', array(), CUSTOM_CROP_VERSION);
    }

    public function add_featured_image_display_settings($content, $post_id, $attachment_id = false)
    {

        if (empty($attachment_id)) return $content;

        $this->get_sizes();
        $default = reset($this->sizes);

        $metadata = wp_get_attachment_metadata($attachment_id);

        ob_start();

        ?>
        <script type="text/template" id="tmpl-modal-content" class="hide-menu">
            <div class="image-editor media-frame hide-menu hide-router custom-crop-modal">
                <div class="media-frame-title">
                    <h1><?php _e('Modify thumbnail'); ?></h1>
                </div>
                <div class="media-frame-content">
                    <div class="attachments-browser">
                        <div class="crop-area">
                            <div class="cropped-img" data-attachment-id="<?php echo $attachment_id; ?>" >
                                <img src="<?php echo wp_get_attachment_image_url($attachment_id, 'full') ?>"
                                     alt="" class=""/>
                            </div>
                            <div class="margin top"></div>
                            <div class="margin bottom"></div>
                            <div class="margin left"></div>
                            <div class="margin right"></div>
                        </div>
                        <div class="slider-area">
                            <div class="slider"></div>
                        </div>
                        <div class="media-sidebar imgedit-settings">
                            <div class="attachment-details">
                                <h2><?php _e('Preview'); ?></h2>

                                <div class="preview" data-left="0" data-top="0" >
                                    <img src="<?php echo wp_get_attachment_image_url($attachment_id, 'full') ?>"
                                         alt="" class="">
                                </div>
                            </div>
                            <div class="imgedit-group">
                                <div class="imgedit-group-top">
                                    <h2>Image Size</h2>
                                    <button type="button" class="dashicons dashicons-editor-help imgedit-help-toggle"
                                            onclick="imageEdit.toggleHelp(this);return false;" aria-expanded="false">
                                        <span class="screen-reader-text">Image Crop Help</span></button>

                                    <div class="imgedit-help">
                                        <p>To crop the image, click on it and drag to make your selection.</p>

                                        <p><strong>Crop Aspect Ratio</strong><br>
                                            The aspect ratio is the relationship between the width and height. You can
                                            preserve the aspect ratio by holding down the shift key while resizing your
                                            selection. Use the input box to specify the aspect ratio, e.g. 1:1 (square),
                                            4:3, 16:9, etc.</p>
                                    </div>
                                </div>

                                <fieldset class="imgedit-crop-ratio">

                                    <legend>Sizes:</legend>
                                    <div class="nowrap">
                                        <select id="sizes">
                                            <?php
                                            foreach ($this->sizes as $size => $size_opts) {
                                                ?>
                                                <option value="<?php echo $size; ?>"
                                                        data-width="<?php echo $size_opts[1]; ?>"
                                                        data-height="<?php echo $size_opts[2]; ?>"
                                                        <?php
                                                        if (isset($metadata['sizes'][$size])) {
                                                            foreach ($metadata['sizes'][$size] as $param=>$param_val) {
                                                                echo ' data-saved-' . $param . '="' . $param_val . '" ';
                                                            }
                                                        }
                                                        ?>
                                                ><?php echo $size_opts[0]; ?></option>
                                                <?php
                                            }

                                            ?>
                                        </select>
                                    </div>
                                </fieldset>
                                <fieldset class="imgedit-crop-ratio">
                                    <legend>R - Ratio:</legend>
                                    <div class="nowrap">
                                        <label><span class="screen-reader-text">crop ratio width</span>
                                            <input type="text" id="crop-width"
                                                   onchange=""
                                                   value="<?php echo $default[1]; ?>"/>
                                        </label>
                                        <span class="imgedit-separator">:</span>
                                        <label><span class="screen-reader-text">crop ratio height</span>
                                            <input type="text" id="crop-height" value="<?php echo $default[2];; ?>"/>
                                        </label>
                                    </div>
                                </fieldset>

                            </div>
                            <div class="imgedit-group">
                                <button type="button"
                                        class="button image-actions center"><span
                                        class="dashicons dashicons-move"></span>
                                    Center
                                </button>
                                <button type="button"
                                        class="button image-actions fit-in">
                                    <span class="dashicons dashicons-editor-contract"></span>
                                    Fit in
                                </button>
                                <button type="button"
                                        class="button image-actions cover">
                                    <span class="dashicons dashicons-editor-expand"></span>
                                    Cover
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="media-frame-toolbar">
                    <div class="media-toolbar">
                        <div class="media-toolbar-secondary"></div>
                        <div class="media-toolbar-primary search-form">
                            <button type="button" id="custom_crop_done"
                                    class="button media-button button-primary button-large done">Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <a href="#" id="modify_thumbnail"> <?php _e('Modify thumbnail'); ?></a>

        <?php


        return $content . ob_get_clean();
    }

    public static function inline_views()
    {
        wp_get_attachment_image_src();
        wp_generate_attachment_metadata()
        ?>

        <?php
    }

    public function get_sizes()
    {

        if (empty($this->sizes))
            $this->sizes = apply_filters('custom_crop_sizes', array(
                'custom-crop' => array(__('Custom Crop'), 300, 200),
                'custom-crop4:3' => array(__('Custom Crop 4:3'), 400, 300),
            ));

        return $this->sizes;
    }

    public function add_image_size()
    {

    }

    public function ajax_done()
    {
        check_admin_referer('custom_crop');

        $attachment_id = absint($_POST['attachment_id']);

        $size_meta = array(
            'mime-type' => 'image/jpeg',
        );
        $size = sanitize_key($_POST['size']);

        $size_meta['width'] = absint($_POST['area_size'][0]);
        $size_meta['height'] = absint($_POST['area_size'][1]);

        $size_meta['img_width'] = absint($_POST['img_size'][0]);
        $size_meta['img_height'] = absint($_POST['img_size'][1]);

        $size_meta['x'] = 0;
        $size_meta['y'] = 0;

        if (isset($_POST['position'])) {
            $size_meta['x'] = intval($_POST['position'][0]);
            $size_meta['y'] = intval($_POST['position'][1]);
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        var_dump($meta);
        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($meta['file']);

        $origin_path = $upload_dir['basedir'] . '/' . $meta['file'];
        $size_meta['file'] = $path_parts['filename'] . '-' . $size . '.jpg';
        $dst_path = $upload_dir['basedir'] . '/' . $path_parts['dirname'] . '/' . $size_meta['file'];

        if (imagejpeg($this->crop($origin_path, $size_meta['width'], $size_meta['height'], $size_meta['img_width'], $size_meta['img_height'], $size_meta['x'], $size_meta['y']), $dst_path, 90))
            $meta['sizes'][$size] = $size_meta;

        wp_update_attachment_metadata($attachment_id, $meta);

        exit();
    }

    public function save_cropped_sizes($metadata, $attachment_id)
    {

        if (!isset($metadata['image_meta'])) return $metadata;

        $oldmeta = wp_get_attachment_metadata($attachment_id);

        if (empty($oldmeta)) return $metadata;

        $this->get_sizes();

        foreach ($this->sizes as $size => $size_opts) {
            if (isset($oldmeta['sizes'][$size])) $metadata['sizes'][$size] = $oldmeta['sizes'][$size];
        }

        return $metadata;
    }

    public function crop($path, $width, $height, $img_width, $img_height, $x, $y)
    {


        //Create images resources
        $origin_image = imagecreatefromstring(file_get_contents($path));

        //Create resulting image
        $cropped_result = imagecreatetruecolor($width, $height);
        imagesavealpha($cropped_result, true);
        $color = imagecolorallocatealpha($cropped_result, 255, 255, 255, 0);
        imagefill($cropped_result, 0, 0, $color);

        $origin_width = imagesx($origin_image);
        $origin_height = imagesy($origin_image);

        imagecopyresampled($cropped_result, $origin_image, $x, $y, 0, 0, $img_width, $img_height, $origin_width, $origin_height);

        return $cropped_result;

    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Custom_Crop::get_instance();