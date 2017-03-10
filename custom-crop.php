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

    private function __construct()
    {
        add_action('load-page.php', array($this, 'init'));
        add_action('load-page-new.php', array($this, 'init'));
        add_action('load-post.php', array($this, 'init'));
        add_action('load-post-new.php', array($this, 'init'));
        add_filter('admin_post_thumbnail_html', array($this, 'add_featured_image_display_settings'), 10, 3);
    }

    public function init()
    {

        add_action('admin_enqueue_scripts', array($this, 'assets'));
//        add_action('admin_footer', array($this, 'inline_views'));

    }

    public function assets()
    {
        wp_enqueue_media();

        wp_enqueue_script('custom-crop', plugin_dir_url(__FILE__) . 'custom-crop.js', array(
            'jquery',
            'jquery-ui-slider',
            'jquery-ui-draggable', ), CUSTOM_CROP_VERSION);


        wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'jquery-ui.css');
        wp_enqueue_style('custom-crop', plugin_dir_url(__FILE__) . 'style.css', array(), CUSTOM_CROP_VERSION);
    }

    public function add_featured_image_display_settings($content, $post_id, $attachment_id = false)
    {

        if (empty($attachment_id)) return $content;

        $ratio = apply_filters('custom_crop_ratio', array(400, 300));

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
                            <div class="cropped-img">
                                <img src="<?php echo wp_get_attachment_image_url($attachment_id, 'full') ?>" alt="" class="">
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

                                <div class="preview">
                                    <img src="<?php echo wp_get_attachment_image_url($attachment_id, 'full') ?>" alt="" class="">
                                </div>
                            </div>
                            <div class="imgedit-group">
                                <div class="imgedit-group-top">
                                    <h2>Image Size</h2>
                                    <button type="button" class="dashicons dashicons-editor-help imgedit-help-toggle" onclick="imageEdit.toggleHelp(this);return false;" aria-expanded="false"><span class="screen-reader-text">Image Crop Help</span></button>

                                    <div class="imgedit-help">
                                        <p>To crop the image, click on it and drag to make your selection.</p>

                                        <p><strong>Crop Aspect Ratio</strong><br>
                                            The aspect ratio is the relationship between the width and height. You can preserve the aspect ratio by holding down the shift key while resizing your selection. Use the input box to specify the aspect ratio, e.g. 1:1 (square), 4:3, 16:9, etc.</p>
                                    </div>
                                </div>

                                <fieldset class="imgedit-crop-ratio">
                                    <legend>R - Ratio:</legend>
                                    <div class="nowrap">
                                        <label><span class="screen-reader-text">crop ratio width</span>
                                            <input type="text" id="crop-width"
                                                   onchange=""
                                                   value="<?php echo $ratio[0]; ?>" />
                                        </label>
                                        <span class="imgedit-separator">:</span>
                                        <label><span class="screen-reader-text">crop ratio height</span>
                                            <input type="text" id="crop-height" value="<?php echo $ratio[1]; ?>" />
                                        </label>
                                    </div>
                                </fieldset>

                            </div>
                            <div class="imgedit-group">
                                <button type="button"
                                        class="button image-actions center"><span class="dashicons dashicons-move"></span>
                                    Center
                                </button>
                                <button type="button"
                                        class="button image-actions fit-in">
                                    <span class="dashicons dashicons-editor-contract"></span>
                                    Fit in
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
        ?>

        <?php
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