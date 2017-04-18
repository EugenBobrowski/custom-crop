'use strict';

(function ($) {
    var $body, $window, $modal, $area, $img, $preview, size;
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
            initialize: function () {
                this.listenTo(this.model, "change", this.render);
            },
            attach: function () {
                $img.data('h', $img.find('img').height())
                    .data('w', $img.find('img').width())
                    .addClass('responsive');
                this.resize_area();
                this.slider_init();
                this.zoom(1);
                $img.draggable();

            },
            open: function () {
                this.change_size();
            },
            resize_area: function (e) {

                var $size = $modal.find('.media-router>a.active');
                var size = {
                        w: $size.data('width'),
                        h: $size.data('height')
                    },
                    area = {
                        w: $area.outerWidth(),
                        h: $area.outerHeight(),
                        zoom: 1
                    },
                    margin = {};

                if (size.w > area.w || size.h > area.h) {
                    area.ratio = area.w / area.h;
                    size.ratio = size.w / size.h;

                    if (size.ratio > area.ratio) {
                        //horizontal
                        ares.zoom = area.w / size.w;
                    } else {
                        //vertical
                        area.zoom = area.h / size.h;
                    }
                }

                margin.x = (area.w - (size.w * area.zoom)) / 2;
                margin.y = (area.h - (size.h * area.zoom)) / 2;
                console.log(area, size, area.x, (size.x * area.zoom));

                $area.css('padding', margin.y + 'px ' + margin.x + 'px');
                $area.find('.margin.top').height(margin.y);
                $area.find('.margin.bottom').height(margin.y);
                $area.find('.margin.left').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.find('.margin.right').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.data(area);

                this.preview({
                    size: size
                });

                return this;
            },
            change_size: function (e) {
                var $this;

                if (e !== undefined) {
                    $this = $(e.target).closest('a');
                    $this.parent().find('.active').removeClass('active');
                    $this.addClass('active');
                } else {
                    $this = $modal.find('.media-router>a.active');
                }

                size = $this.data();

                if (size.savedWidth !== undefined) $modal.find('.button.delete').fadeIn();
                else $modal.find('.button.delete').fadeOut();

                this.resize_area();

                size.max_zoom = this.get_max_zoom(true);
                if (size.max_zoom < 1) size.max_zoom = 1;

                $modal.find('.origin-zoom').css('left', (1 / size.max_zoom * 100) + '%' );

                console.log(size.max_zoom);

                if (size.savedImg_width != undefined && size.savedImg_height != undefined) {
                    var zoom;

                    zoom = size.savedImg_width / $img.data('w');

                    $img.width(size.savedImg_width)
                        .height(size.savedImg_height);

                    this.slider_set_zoom(zoom);

                    this.preview();

                }
                if (size.savedX != undefined && size.savedY != undefined) {
                    $img.css('left', size.savedX).css('top', size.savedY);
                    this.preview({
                        position: {
                            left: size.savedX,
                            top: size.savedY
                        }
                    });
                }


            },
            preview: function (opt) {
                if (typeof opt == 'undefined') opt = {};

                if (typeof opt.size == 'object') {
                    var w = $preview.width();
                    $preview.height(w / opt.size.w * opt.size.h);
                } else {
                    opt.size = {
                        w: $area.data('w'),
                        h: $area.data('h')
                    }
                }

                if ('object' == typeof opt.position) {
                    $preview.find('img')
                        .css('top', opt.position.top / opt.size.h * $preview.height())
                        .css('left', opt.position.left / opt.size.w * $preview.width());
                    $img.data('top', opt.position.top).data('left', opt.position.left);
                    return this;
                }

                $preview.find('img')
                    .width($img.outerWidth() / opt.size.w * 100 + '%')
                    .height($img.outerHeight() / opt.size.h * 100 + '%');


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
                this.preview(ui);
            },
            zoom: function (zoom) {
                $img.width($img.data('w') * zoom)
                    .height($img.data('h') * zoom);
                this.preview();
            },

            get_max_zoom: function (cover) {
                if (cover == undefined) cover = false;

                var area = {
                        w: $area.width(),
                        h: $area.height()
                    },
                    img = {
                        w: $img.data('w'),
                        h: $img.data('h')
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
                    top: ($area.height() - $img.height()) / 2,
                    left: ($area.width() - $img.width()) / 2
                };
                $img.css('left', pos.left)
                    .css('top', pos.top);
                this.preview({
                    position: pos
                });
            },
            delete: function (e) {
                var $selected = $modal.find('.media-router>a.active');
                $.post(custom_crop_ajax.url, {
                    action: custom_crop_ajax.action,
                    _wpnonce: custom_crop_ajax._wpnonce,
                    attachment_id: $img.data('attachment-id'),
                    size: $selected.data('size'),
                    remove: true

                }, function (response) {
                    $selected
                        .removeAttr('saved-width')
                        .removeAttr('saved-height')
                        .removeAttr('saved-x')
                        .removeAttr('saved-y')
                        .removeAttr('saved-img_width')
                        .removeAttr('saved-img_height');

                    console.log($selected.data());

                    $selected
                        .removeData('saved-width')
                        .removeData('saved-height')
                        .removeData('saved-x')
                        .removeData('saved-y')
                        .removeData('saved-img_width')
                        .removeData('saved-img_height');

                    console.log($selected.data());

                    $selected.find('img').attr('src', custom_crop_ajax.placeholder);
                    $modal.find('.button.delete').fadeOut();
                    if (response.file_deleted) {

                    }


                });
            },
            save: function (e, close) {
                var _this = this;
                var $selected = $modal.find('.media-router>a.active');
                $.post(custom_crop_ajax.url, {
                    action: custom_crop_ajax.action,
                    _wpnonce: custom_crop_ajax._wpnonce,
                    attachment_id: $img.data('attachment-id'),
                    size: $selected.data('size'),
                    area_size: [$area.width(), $area.height()],
                    img_size: [$img.width(), $img.height()],
                    position: [$img.data('left'), $img.data('top')]

                }, function (response) {
                    // console.log(response, custom_crop_ajax, $img.data('attachment-id'));

                    console.log($selected.data());
                    $selected
                        .data('saved-width', $area.width())
                        .data('saved-height', $area.height())
                        .data('saved-x', $img.data('left'))
                        .data('saved-y', $img.data('top'))
                        .data('saved-img_width', $img.width())
                        .data('saved-img_height', $img.height());

                    $selected.find('img').attr('src', response.url + '?time=' + new Date().getTime());

                    $modal.find('.button.delete').fadeIn();

                    if (close != undefined && close == true) _this.close(e);
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
            // A controller object is expected, but let's just pass
            // a fake one to illustrate this proof of concept without
            // getting console errors.
            controller: {
                trigger: function (e) {
                    console.log(e);

                    if (e == 'attach') {
                        // $body.find( ".custom-crop-modal" ).find( ".slider" ).slider();


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

                        // $body.find( ".custom-crop-modal" ).find( ".slider" ).slider();


                    }
                }
            }

        });

        // When the user clicks a button, open a modal.
        $body.on('click', '#modify_thumbnail', function (event) {
            event.preventDefault();
            // Assign the ModalContentView to the modal as the `content` subview.
            // Proxies to View.views.set( '.media-modal-content', content );
            // modal.content(new ModalContentView());
            if ($modal == undefined)
                modal.content(new ModalContentView());

            // Out of the box, the modal is closed, so we need to open() it.
            modal.open();
        });


    });
})(jQuery);