'use strict';

(function ($) {
    var $body, $window, $modal, $area, $img, $preview;
    $(document).ready(function () {
        $body = $('body');
        $window = $(window);

        var cropViewObject = {
            className: "custom-crop-modal",
            template: wp.template('modal-content'),

            events: {
                "click .button.done": "done",
                "slide .slider": "slide",
                "drag .cropped-img": "drag",
                "change #crop-width": "resize",
                "change #crop-height": "resize",
                "click .fit-in": "fit_in",
                "click .center": "center"
            },
            initialize: function () {
                this.listenTo(this.model, "change", this.render);
            },
            resize: function (e) {
                // console.log(e);
                var w = $body.find(".custom-crop-modal").find('#crop-width').val(),
                    h = $body.find(".custom-crop-modal").find('#crop-height').val(),
                    margin = {
                        x: ($area.outerWidth() - w) / 2,
                        y: ($area.outerHeight() - h) / 2
                    };
                // console.log(area.outerWidth() + ' - ' + w);
                // console.log(area.outerHeight() + ' - ' + h);
                $area.css('padding', margin.y + 'px ' + margin.x + 'px');
                $area.find('.margin.top').height(margin.y);
                $area.find('.margin.bottom').height(margin.y);
                $area.find('.margin.left').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.find('.margin.right').width(margin.x)
                    .css('top', margin.y)
                    .css('bottom', margin.y);
                $area.data('w', w).data('h', h);

                this.preview({
                    size: {
                        w: w,
                        h: h
                    }});

                return this;
            },
            preview: function (opt) {
                if (typeof opt == 'undefined') opt = {};

                if (typeof opt.size == 'object') {
                    var w = $preview.width();
                    $preview.height(w/opt.size.w * opt.size.h);
                } else {
                    opt.size = {
                        w: $area.data('w'),
                        h: $area.data('h')
                    }
                }

                if ('object' == typeof opt.position) {
                    $preview.find('img')
                        .css('top', opt.position.top/opt.size.h * $preview.height())
                        .css('left', opt.position.left/opt.size.w * $preview.width());
                    return this;
                }

                $preview.find('img')
                    .width($img.outerWidth()/opt.size.w*100 + '%')
                    .height($img.outerHeight()/opt.size.h*100 + '%');


                return this;
            },
            slider_init: function () {

                this.resize();

                $img.data('h', $img.find('img').height())
                    .data('w', $img.find('img').width())
                    .addClass('responsive');

                this.slide({}, {value: 100});
                $img.draggable();

                $modal.find(".slider").slider({
                    value: 100
                });

            },


            slide: function (e, ui) {
                if (e == false) $modal.find(".slider").slider({
                    value: ui.value
                });
                this.zoom(ui.value / 100)
            },
            drag: function (e, ui) {
                console.log(ui);
                this.preview(ui);
            },
            zoom: function (zoom) {
                $img.width($img.data('w') * zoom)
                    .height($img.data('h') * zoom);

                this.preview()
            },
            fit_in: function () {
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

                if (img.ratio > area.ratio) {
                    //horizontal
                    if (img.w <= area.w) return this;
                    zoom = area.w / img.w;
                } else {
                    //vertical
                    if (img.h <= area.h) return this;
                    zoom = area.h / img.h;
                }

                this.slide(false, {value: zoom*100});

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
            done: function (_) {
                console.log('view.done');

                modal.close();
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


                        $window.resize(cropViewObject.resize);
                    }
                    else if (e == 'open') {
                        $modal = $body.find(".custom-crop-modal");
                        $img = $modal.find('.cropped-img');
                        $area = $modal.find('.crop-area');
                        $preview = $modal.find('.preview');

                        // $body.find( ".custom-crop-modal" ).find( ".slider" ).slider();
                        cropViewObject.slider_init();

                    }
                }
            }

        });

        // When the user clicks a button, open a modal.
        $body.on('click', '#modify_thumbnail', function (event) {
            event.preventDefault();
            // Assign the ModalContentView to the modal as the `content` subview.
            // Proxies to View.views.set( '.media-modal-content', content );
            modal.content(new ModalContentView());
            // Out of the box, the modal is closed, so we need to open() it.
            modal.open();
        });


    });
})(jQuery);