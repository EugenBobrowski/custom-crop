<?php

/*
Plugin Name: Custom  Crop
Plugin URI: http://wordpress.org/plugins/antispam/
Description: Custom Crop Plugin
Author: MRKS
Version: 1.5
*/

define('CUSTOM_CROP_VERSION', (WP_DEBUG) ? time() : '1.5');

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
        add_action('admin_footer', array($this, 'modal'));
//        add_action('admin_footer', array($this, 'inline_views'));

    }

    public function assets()
    {
        wp_enqueue_media();

        $this->get_sizes();

        wp_enqueue_script('custom-crop', plugin_dir_url(__FILE__) . 'js/custom-crop.js', array(
            'jquery',
            'jquery-ui-slider',
            'jquery-ui-draggable',), CUSTOM_CROP_VERSION);
        wp_localize_script('custom-crop', 'custom_crop_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            '_wpnonce' => wp_create_nonce('custom_crop'),
            'action' => 'custom_crop',
            'placeholder' => plugin_dir_url(__FILE__) . 'css/placeholder.png',
            'sizes' => $this->sizes,
        ));


        wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.css');
        wp_enqueue_style('custom-crop', plugin_dir_url(__FILE__) . 'css/style.css', array(), CUSTOM_CROP_VERSION);
    }

    public function modal()
    {
        $this->get_sizes();
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

                        foreach ($this->sizes as $size_id => $size_opts) {
                            ?>
                            <a href="#" class="media-menu-item <?php echo $active; ?>"
                               data-size-id="<?php echo $size_id; ?>"
                            >
                                <span class="prev-icon">
                                    <img src="<?php echo $placeholder; ?>" alt="">
                                </span>

                                <?php echo $size_opts['title']; ?></a>
                            <?php
                            $active = '';
                        }

                        ?>
                    </div>
                </div>
                <div class="media-frame-content">
                    <div class="attachments-browser">
                        <div class="crop-area">
                            <div class="cropped-img">
                                <img src="<?php echo $placeholder; ?>"
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
                            <div class="attachment-details imgedit-group">
                                <h2><?php _e('Preview'); ?></h2>

                                <div class="preview" data-left="0" data-top="0">
                                    <img src="<?php echo $placeholder; ?>"
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

                            <span class="save-spinner spinner"
                                  style="float: right; margin: 20px 0 0 0;"></span>

                        </div>
                    </div>
                </div>
            </div>
        </script>
        <?php
    }

    public function add_featured_image_display_settings($content, $post_id, $attachment_id = false)
    {

        if (empty($attachment_id) || get_post_thumbnail_id($post_id) != $attachment_id) return $content;

        $this->get_sizes($post_id);

        $metadata = wp_get_attachment_metadata($attachment_id);
        $src = wp_get_attachment_image_src($attachment_id, 'full');

        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($metadata['file']);
        $sizes_dir_url = $upload_dir['baseurl'] . '/' . $path_parts['dirname'];

        ob_start();

        ?>
        <a href="#"
           id="modify_thumbnail"
           class="custom-crop-modal-open-link"
           data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
           data-available="<?php echo esc_attr(json_encode(apply_filters('available_thumbnail_ccrop_sizes', array(), $post_id))); ?>"
           data-disable="<?php echo esc_attr(json_encode(apply_filters('disable_thumbnail_ccrop_sizes', array(), $post_id))); ?>"
        > <?php _e('Modify thumbnail'); ?></a>

        <?php
        $avalieble_files = array();
        foreach ($this->sizes as $size_id => $size_opts) {

            if (!isset($metadata['sizes'][$size_id])) continue;

            $avalieble_files[] = '<a href="' . $sizes_dir_url . '/' . $metadata['sizes'][$size_id]['file'] . '">' . $size_opts['title'] . '</a>';

        }
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

    public function get_sizes($post_id = null)
    {

        if (empty($this->sizes))
            $this->sizes = apply_filters('custom_crop_sizes', array(
                'custom-crop' => array(__('Custom Crop'), 300, 200),
                'custom-crop43' => array(__('Custom Crop 4:3'), 400, 300),
            ), $post_id);

        foreach ($this->sizes as $size_id => $size) {
            if (isset($size['width']) && isset($size['width']) && isset($size['title'])) continue;
            if (count($size) == 3) {
                $this->sizes[$size_id] = array(
                    'title' => $size[0],
                    'width' => $size[1],
                    'height' => $size[2]
                );
            } else unset($this->sizes[$size_id]);

        }


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
        $do = (!empty($_POST['do'])) ? sanitize_key($_POST['do']) : '';
        $size = (!empty($_POST['size'])) ? sanitize_key($_POST['size']) : '';

        switch ($do) {
            case 'get_attachment':
                $this->ajax_get_attachmets($attachment_id);
                break;
            case 'remove':
                $this->ajax_remove($attachment_id, $size);
                break;
            default:
                $this->ajax_crop($attachment_id, $size);
                break;
        }

        exit();


    }

    public function ajax_get_attachmets($attachment_id)
    {
        $meta = wp_get_attachment_metadata($attachment_id);
        $upload_dir = wp_upload_dir();
        $path_parts = pathinfo($meta['file']);
        $sizes_dir_url = $upload_dir['baseurl'] . '/' . $path_parts['dirname'];

        $meta['file'] = $upload_dir['baseurl'] . '/' . $meta['file'];


        $this->get_sizes();
        foreach ($meta['sizes'] as $size_id => $size) {
            if (!isset($this->sizes[$size_id])) {
                unset($meta['sizes'][$size_id]);
                continue;
            }

            $meta['sizes'][$size_id]['file'] = $sizes_dir_url . '/' . $meta['sizes'][$size_id]['file'];
            $meta['sizes'][$size_id]['file'] = add_query_arg(array('t' => $meta['sizes'][$size_id]['timestamp']), $meta['sizes'][$size_id]['file']);
        }

        wp_send_json($meta);

    }

    public function ajax_remove($attachment_id, $size)
    {

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

    public function ajax_crop($attachment_id, $size)
    {
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
        $dst_url = add_query_arg(array('t' => $size_meta['timestamp']), $dst_url);

        if (imagejpeg($this->crop($origin_path, $size_meta['width'], $size_meta['height'], $size_meta['img_width'], $size_meta['img_height'], $size_meta['x'], $size_meta['y']), $dst_path, 90))
            $meta['sizes'][$size] = $size_meta;

        wp_update_attachment_metadata($attachment_id, $meta);

        do_action('update_cropshop_size', $size, $attachment_id);

        $size_meta['file'] = $dst_url;

        wp_send_json(array(
            'url' => $dst_url,
            'meta' => $size_meta,
        ));
    }

    public function save_cropped_sizes($metadata, $attachment_id)
    {

        if (!isset($metadata['image_meta'])) return $metadata;

        $oldmeta = wp_get_attachment_metadata($attachment_id);

        if (empty($oldmeta)) return $metadata;

        $this->get_sizes();

        foreach ($this->sizes as $size_id => $size_opts) {
            if (isset($oldmeta['sizes'][$size_id])) $metadata['sizes'][$size_id] = $oldmeta['sizes'][$size_id];
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