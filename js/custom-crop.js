'use strict';

(function ($) {
    var $body, $window, $modal, $area, $img, $preview,
        size,
        sizes = {},
        Attachments = {},
        attachment = {};
    $(document).ready(function () {
        $body = $('body');
        $window = $(window);

        var cropViewObject = {
            className: "custom-crop-modal",
            template: wp.template('modal-content'),

            events: {
                "click .button.done": "done",
                "click .button.save": "save",
                "click .button.cancel": "close",
                "click .button.delete": "delete",
                "slide .slider": "slide",
                "drag .cropped-img": "drag",
                "change #sizes": "select_size",
                "click .fit-in": "fit_in",
                "click .cover": "cover",
                "click .origin-zoom": "origin_zoom",
                "click .center": "center",
                "click .media-router>a": "change_size"
            },
            // initialize: function () {
            //     // this.listenTo(this.model, "change", this.render);
            // },
            attach: function () {


            },
            open: function () {
                var _ = this;
                this.get_attachment(function () {
                    _.allow_sizes();
                    _.change_size();
                    _.resize_area();
                    $img.draggable();

                });

            },

            resize_area: function (e) {

                var area = {
                        max_w: $area.outerWidth(),
                        max_h: $area.outerHeight(),
                        zoom: 1
                    },
                    margin = {};

                if (size.width > area.max_w || size.height > area.max_h) {
                    area.ratio = area.max_w / area.max_h;
                    size.ratio = size.width / size.height;

                    if (size.ratio > area.ratio) {
                        //horizontal
                        area.zoom = area.max_w / size.w;
                    } else {
                        //vertical
                        area.zoom = area.max_h / size.height;
                    }
                }

                margin.x = (area.max_w - (size.width * area.zoom)) / 2;
                margin.y = (area.max_h - (size.height * area.zoom)) / 2;

                $area.css('padding', margin.y + 'px ' + margin.x + 'px');
                $area.find('.margin.top').height(margin.y);
                $area.find('.margin.bottom').height(margin.y);
                $area.find('.margin.left').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.find('.margin.right').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.data({
                    w: size.width,
                    h: size.height,
                    zoom: area.zoom
                });

                this.preview({
                    size: size
                });

                return this;
            },
            allow_sizes: function () {
                var $sizes = $modal.find('.media-router>a');
                var show_all = false;
                if ( typeof sizes.available !== 'object') show_all = true;

                $sizes.each(function () {
                    var $this = $(this);
                    var size_id = $this.data('size-id');

                    if ( typeof size_id !== 'string' && !show_all) return true;

                    if (!show_all && sizes.available.indexOf(size_id) < 0) {
                        $this.hide();
                    }
                    else {
                        $this.show();
                    }

                });

                // if ($sizes.filter('.active'))
                if($sizes.filter('a.active:visible').length === 0) {
                    $sizes.removeClass('active');

                    $sizes.filter(':visible').first().addClass('active');
                }
            },
            change_size: function (e) {
                var $this,
                    current_size = {
                        x: 0,
                        y: 0
                    },
                    zoom = 1;

                if (e !== undefined) {
                    $this = $(e.target).closest('a');
                    $this.parent().find('.active').removeClass('active');
                    $this.addClass('active');
                } else {
                    $this = $modal.find('.media-router>a.active');
                }

                size = {id: $this.data('size-id')};
                $.extend(size, custom_crop_ajax.sizes[size.id]);


                this.resize_area();

                var area_zoom = $area.data('zoom');

                size.max_zoom = this.get_max_zoom(true);
                if (size.max_zoom < 1) size.max_zoom = 1;

                $modal.find('.origin-zoom').css('left', (1 / size.max_zoom * 100) + '%');

                if (typeof attachment.sizes[size.id] !== 'undefined') {
                    zoom = attachment.sizes[size.id].img_width / attachment.width;
                    $modal.find('.button.delete').fadeIn();
                    current_size.x = attachment.sizes[size.id].x * area_zoom;
                    current_size.y = attachment.sizes[size.id].y * area_zoom;
                } else {
                    $modal.find('.button.delete').fadeOut();
                }

                this.slider_set_zoom(zoom);
                this.zoom(zoom);

                this.preview();


                // if (size.savedX != undefined && size.savedY != undefined) {
                //     current_size.x = size.savedX * area_zoom;
                //     current_size.y = size.savedY * area_zoom;
                // }
                $img.css('left', current_size.x).css('top', current_size.y);
                this.preview({
                    position: {
                        left: current_size.x,
                        top: current_size.y
                    }
                });


            },
            preview: function (opt) {
                if (typeof opt == 'undefined') opt = {};

                if (typeof opt.size == 'object') {
                    var w = $preview.width();
                    $preview.height(w / opt.size.width * opt.size.height);
                } else {
                    opt.size = {
                        w: $area.data('w'),
                        h: $area.data('h')
                    };
                }

                if ('object' == typeof opt.position) {
                    $preview.find('img')
                        .css('top', opt.position.top / opt.size.h * $preview.height())
                        .css('left', opt.position.left / opt.size.w * $preview.width());
                    $img.data('top', opt.position.top).data('left', opt.position.left);
                    return this;
                }

                $preview.find('img')
                    .width($img.outerWidth() / opt.size.w / $area.data('zoom') * 100 + '%')
                    .height($img.outerHeight() / opt.size.h / $area.data('zoom') * 100 + '%');

                return this;
            },
            slider_init: function () {

                $modal.find(".slider").slider({
                    value: 100
                });

            },
            slider_set_zoom: function (zoom) {
                $modal.find(".slider").slider({
                    value: zoom / size.max_zoom * 100
                });
            },
            slide: function (e, ui) {
                this.zoom(size.max_zoom * ui.value / 100);
                this.preview()
            },
            drag: function (e, ui) {
                this.preview({
                    position: {
                        top: ui.position.top / $area.data('zoom'),
                        left: ui.position.left / $area.data('zoom')
                    }
                });
            },
            zoom: function (zoom) {
                $img.width(attachment.width * zoom * $area.data('zoom'))
                    .height(attachment.height * zoom * $area.data('zoom'));
                this.preview();
            },

            get_max_zoom: function (cover) {
                if (cover === undefined) cover = false;
                var area = {
                        w: $area.data('w'),
                        h: $area.data('h')
                    },
                    img = {
                        w: attachment.width,
                        h: attachment.height
                    },
                    zoom;

                area.ratio = area.w / area.h;
                img.ratio = img.w / img.h;

                var horizontal = (img.ratio > area.ratio);

                horizontal = (cover) ? !horizontal : horizontal;

                if (horizontal) {
                    //horizontal
                    zoom = area.w / img.w;
                } else {
                    //vertical
                    zoom = area.h / img.h;
                }
                return zoom;
            },
            origin_zoom: function (e) {
                this.zoom(1);
                this.slider_set_zoom(1);
            },
            fit_in: function (e) {

                var zoom = this.get_max_zoom();

                if (zoom > 1) {
                    alert('The original image to small');
                    zoom = 1;
                }

                this.zoom(zoom);

                this.slider_set_zoom(zoom);

                this.center();

                return this;
            },
            cover: function (e) {

                var zoom = this.get_max_zoom(true);

                this.zoom(zoom);

                this.slider_set_zoom(zoom);

                this.center();

                return this;
            },
            center: function () {
                var pos = {
                    top: ($area.data('h') - $img.height() / $area.data('zoom') ) / 2,
                    left: ($area.data('w') - $img.width() / $area.data('zoom') ) / 2
                };
                $img.css('left', pos.left * $area.data('zoom'))
                    .css('top', pos.top * $area.data('zoom'));
                this.preview({
                    position: pos
                });
            },
            get_attachment: function (callback) {

                $.post(custom_crop_ajax.url, {
                    action: custom_crop_ajax.action,
                    do: 'get_attachment',
                    _wpnonce: custom_crop_ajax._wpnonce,
                    attachment_id: attachment.id

                }, function (response) {

                    $.extend(attachment, response);

                    $img.find('img').attr('src', attachment.file);
                    $preview.find('img').attr('src', attachment.file);

                    $modal.find('.media-router>a').each(
                        function () {
                            var $this = $(this);
                            if (typeof attachment.sizes[$this.data('size-id')] !== 'undefined') {
                                $this.find('.prev-icon').find('img')
                                    .attr('src', attachment.sizes[$this.data('size-id')].file);
                            } else {
                                $this.find('.prev-icon').find('img')
                                    .attr('src', custom_crop_ajax.placeholder);
                            }
                        }
                    );

                    callback.call();

                });
            },
            delete: function (e) {
                var $selected = $modal.find('.media-router>a.active');
                $.post(custom_crop_ajax.url, {
                    action: custom_crop_ajax.action,
                    do: 'remove',
                    _wpnonce: custom_crop_ajax._wpnonce,
                    attachment_id: attachment.id,
                    size: $selected.data('size')

                }, function (response) {
                    $selected
                        .removeAttr('saved-width')
                        .removeAttr('saved-height')
                        .removeAttr('saved-x')
                        .removeAttr('saved-y')
                        .removeAttr('saved-img_width')
                        .removeAttr('saved-img_height');

                    $selected
                        .removeData('saved-width')
                        .removeData('saved-height')
                        .removeData('saved-x')
                        .removeData('saved-y')
                        .removeData('saved-img_width')
                        .removeData('saved-img_height');

                    $selected.find('img').attr('src', custom_crop_ajax.placeholder);
                    $modal.find('.button.delete').fadeOut();
                    if (response.file_deleted) {

                    }


                });
            },
            save: function (e, close) {
                var _this = this;
                var area_zoom = $area.data('zoom');
                var $spinner = $modal.find('.save-spinner');
                var data = {
                    action: custom_crop_ajax.action,
                    _wpnonce: custom_crop_ajax._wpnonce,
                    attachment_id: attachment.id,
                    size: size.id,
                    area_size: [$area.data('w'), $area.data('h')],
                    img_size: [$img.width() / area_zoom, $img.height() / area_zoom],
                    position: [$img.data('left'), $img.data('top')]
                };
                $spinner.addClass('is-active');

                $.post(custom_crop_ajax.url, data, function (response) {

                    if (typeof response.meta !== 'object') {
                        return false;
                    }

                    attachment.sizes[size.id] = response.meta;

                    $modal.find('.media-router>a.active').find('img').attr('src', response.url + '?time=' + new Date().getTime());

                    $modal.find('.button.delete').fadeIn();

                    $spinner.removeClass('is-active');

                    if (close !== undefined && close === true) _this.close(e);
                });
            },
            close: function (e) {

                modal.close();
                return this;
            },
            done: function (e) {

                this.save(e, true);

                return this;
            }

        };
        // Create a modal content view.
        var ModalContentView = wp.Backbone.View.extend(cropViewObject);

        var modal = new wp.media.view.Modal({
            controller: {
                trigger: function (e) {

                    if (e == 'attach') {

                        $window.resize(function (e) {
                            cropViewObject.resize_area(e);
                        });

                        $modal = $body.find(".custom-crop-modal");
                        $img = $modal.find('.cropped-img');
                        $area = $modal.find('.crop-area');
                        $preview = $modal.find('.preview');

                        cropViewObject.attach();
                    }
                    else if (e == 'open') {

                        cropViewObject.open();

                    }
                }
            }

        });

        $body.on('click', '.custom-crop-modal-open-link', function (event) {
            event.preventDefault();
            var $this = $(this);
            var id = $this.data('attachment-id');
            sizes.available = $this.data('available');
            sizes.disable = $this.data('disable');

            if (undefined === id)  return false;

            if (typeof Attachments[id] !== 'object') Attachments[id] = {
                id: id
            };

            attachment = Attachments[id];

            if ($modal === undefined) {
                modal.content(new ModalContentView());
            }

            modal.open();
        });


    });
})(jQuery);