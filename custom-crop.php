<?php

/*
Plugin Name: Custom  Crop
Plugin URI: http://wordpress.org/plugins/antispam/
Description:
Author: MRKS
Version: 1.0
*/

define('CUSTOM_CROP_VERSION', (WP_DEBUG) ? time() : '1.2');

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
        add_filter('wp_get_attachment_image_src', array($this, 'add_timestamp_to_src'), 10, 3);
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
            'action' => 'custom_crop',
            'placeholder' => plugin_dir_url(__FILE__) . 'css/placeholder.png',
        ));


        wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.css');
        wp_enqueue_style('custom-crop', plugin_dir_url(__FILE__) . 'css/style.css', array(), CUSTOM_CROP_VERSION);
    }

    public function add_featured_image_display_settings($content, $post_id, $attachment_id = false)
    {

        if (empty($attachment_id) || get_post_thumbnail_id($post_id) != $attachment_id) return $content;

        $this->get_sizes();

        $metadata = wp_get_attachment_metadata($attachment_id);
        $src = wp_get_attachment_image_src($attachment_id, 'full');

        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($metadata['file']);
        $sizes_dir_url = $upload_dir['baseurl'] . '/' . $path_parts['dirname'];

        ob_start();

        ?>
        <script type="text/template" id="tmpl-modal-content" class="hide-menu">
            <div class="image-editor media-frame hide-menu ">
                <div class="media-frame-title">
                    <h1><?php _e('Modify thumbnail'); ?></h1>
                </div>
                <div class="media-frame-router">
                    <div class="media-router">
                        <?php
                        $active = 'active';
                        $placeholder = plugin_dir_url(__FILE__) . 'css/placeholder.png';

                        foreach ($this->sizes as $size => $size_opts) {
                            if (isset($metadata['sizes'][$size])) {

                                $prev = $sizes_dir_url . '/' . $metadata['sizes'][$size]['file'];
                            } else {
                                $prev = $placeholder;
                            }

                            ?>
                            <a href="#" class="media-menu-item <?php echo $active; ?>"
                               data-size="<?php echo $size; ?>"
                               data-width="<?php echo $size_opts[1]; ?>"
                               data-height="<?php echo $size_opts[2]; ?>"
                                <?php
                                if (isset($metadata['sizes'][$size])) {
                                    foreach ($metadata['sizes'][$size] as $param => $param_val) {
                                        echo ' data-saved-' . $param . '="' . $param_val . '" ';
                                    }
                                }
                                ?>
                            >
                                <span class="prev-icon">
                                    <img src="<?php echo $prev; ?>" alt="">
                                </span>

                                <?php echo $size_opts[0]; ?></a>
                            <?php
                            $active = '';
                        }

                        ?>
                    </div>
                </div>
                <div class="media-frame-content">
                    <div class="attachments-browser">
                        <div class="crop-area">
                            <div class="cropped-img" data-attachment-id="<?php echo $attachment_id; ?>">
                                <img src="<?php echo $src[0]; ?>"
                                     width="<?php echo $src[1]; ?>"
                                     height="<?php echo $src[2]; ?>"
                                     alt="" class=""/>
                            </div>
                            <div class="margin top"></div>
                            <div class="margin bottom"></div>
                            <div class="margin left"></div>
                            <div class="margin right"></div>
                        </div>
                        <div class="slider-area">
                            <div class="slider"></div>
                            <div class="scale">
                                <a href="#100" class="origin-zoom">100%</a>
                            </div>

                        </div>
                        <div class="media-sidebar imgedit-settings">
                            <div class="attachment-details imgedit-group    ">
                                <h2><?php _e('Preview'); ?></h2>

                                <div class="preview" data-left="0" data-top="0">
                                    <img src="<?php echo $src[0]; ?>"
                                         alt="" class="">
                                </div>
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
                                <!--                                <button type="button"-->
                                <!--                                        class="button image-actions cover">-->
                                <!--                                    <span class="dashicons dashicons-search"></span>-->
                                <!--                                    100%-->
                                <!--                                </button>-->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="media-frame-toolbar">
                    <div class="media-toolbar">
                        <div class="media-toolbar-secondary"></div>
                        <div class="media-toolbar-primary search-form">


                            <button type="button" id="custom_crop_done"
                                    class="button media-button button-primary button-large done">Save & close
                            </button>
                            <button type="button" id="custom_crop_save"
                                    class="button media-button button-large save">Save
                            </button>
                            <button type="button" id="custom_crop_delete"
                                    class="button media-button button-large delete">Delete
                            </button>
                            <!--                            <button type="button" id="custom_crop_cancel"-->
                            <!--                                    class="button media-button button-large cancel widget-control-remove">Cancel-->
                            <!--                            </button>-->

                        </div>
                    </div>
                </div>
            </div>
        </script>
        <a href="#"
           id="modify_thumbnail"
           data-metadata="<?php echo esc_attr(json_encode($metadata)); ?>"
        > <?php _e('Modify thumbnail'); ?></a>

        <?php
        $avalieble_files = array();
        foreach ($this->sizes as $size => $size_opts) {

            if (!isset($metadata['sizes'][$size])) continue;

            $avalieble_files[] = '<a href="' . $sizes_dir_url . '/' . $metadata['sizes'][$size]['file'] . '">' . $size_opts[0] . '</a>';

        }
//            var_dump($avalieble_files);
        ?>
        <?php if (count($avalieble_files)) : ?>
        <p class="desc">
            <strong><?php _e('Custom crop sizes:'); ?></strong>
            <?php echo implode(', ', $avalieble_files); ?>
        </p>
    <?php endif; ?>

        <?php


        return $content . ob_get_clean();
    }

    public function get_sizes()
    {

        if (empty($this->sizes))
            $this->sizes = apply_filters('custom_crop_sizes', array(
                'custom-crop' => array(__('Custom Crop'), 300, 200),
                'custom-crop43' => array(__('Custom Crop 4:3'), 400, 300),
            ));

        return $this->sizes;
    }

    public function add_timestamp_to_src($image, $attachment_id, $size)
    {
        if (empty($image[3]) || !is_string($size)) return $image;

        $meta = wp_get_attachment_metadata($attachment_id);

        if (empty($meta['sizes']) ||
            empty($meta['sizes'][$size]) ||
            empty($meta['sizes'][$size]['timestamp'])
        ) return $image;

        $image[0] = add_query_arg(array(
            't' => $meta['sizes'][$size]['timestamp'],
        ), $image[0]);


        return $image;
    }

    public function ajax_done()
    {
        check_admin_referer('custom_crop');

        $attachment_id = absint($_POST['attachment_id']);

        $size = sanitize_key($_POST['size']);


        if ($_POST['remove']) {
            $this->ajax_remove($attachment_id, $size);
        }

        $size_meta = array(
            'mime-type' => 'image/jpeg',
        );

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
        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($meta['file']);

        $origin_path = $upload_dir['basedir'] . '/' . $meta['file'];
        $size_meta['file'] = $path_parts['filename'] . '-' . $size . '.jpg';
        $size_meta['timestamp'] = time();
        $dst_path = $upload_dir['basedir'] . '/' . $path_parts['dirname'] . '/' . $size_meta['file'];
        $dst_url = $upload_dir['baseurl'] . '/' . $path_parts['dirname'] . '/' . $size_meta['file'];


        if (imagejpeg($this->crop($origin_path, $size_meta['width'], $size_meta['height'], $size_meta['img_width'], $size_meta['img_height'], $size_meta['x'], $size_meta['y']), $dst_path, 90))
            $meta['sizes'][$size] = $size_meta;

        wp_update_attachment_metadata($attachment_id, $meta);

        wp_send_json(array(
            'url' => $dst_url,
            'meta' => $meta,
        ));


    }

    public function ajax_remove($attachment_id, $size)
    {
        $response = array();
        $meta = wp_get_attachment_metadata($attachment_id);
        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($meta['file']);

        if (!isset($meta['sizes'][$size]))
            wp_send_json(array(
                'meta_not_exists' => isset($meta['sizes'][$size]),
                'size' => $size,
                'meta' => $meta,
            ));

        $file = $upload_dir['basedir'] . '/' . $path_parts['dirname'] . '/' . $meta['sizes'][$size]['file'];

        if (file_exists($file)) {
            $file_existed = true;
            $file_deleted = unlink($file);
        } else {
            $file_existed = false;
            $file_deleted = false;
        }

        unset($meta['sizes'][$size]);
        wp_update_attachment_metadata($attachment_id, $meta);

        wp_send_json(array(
            'file_existed' => $file_existed,
            'file_deleted' => $file_deleted,
            'file' => $file,
            'meta' => $meta,
        ));

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