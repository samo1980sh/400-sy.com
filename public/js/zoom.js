
import PhotoSwipeLightbox from './photoswipe-lightbox.esm.min.js';
import PhotoSwipe from './photoswipe.esm.min.js';

if ($(".thumbs-slider").length > 0) {
    var direction = $(".tf-product-media-thumbs").data("direction");
    var $mainSlides = $(".tf-product-media-main .swiper-slide");
    var $thumbSlides = $(".tf-product-media-thumbs .swiper-slide");
    var $colorButtons = $(".color-btn");

    function normalizeColor(value) {
      return String(value || "").trim().toLowerCase();
    }

    function getVisibleMainSlides() {
      return $mainSlides.filter(function () {
        return $(this).css("display") !== "none";
      });
    }

    function getVisibleThumbSlides() {
      return $thumbSlides.filter(function () {
        return $(this).css("display") !== "none";
      });
    }

    function syncThumbActiveByVisibleIndex(visibleIndex) {
      $thumbSlides.removeClass("swiper-slide-thumb-active");
      var $visibleThumb = getVisibleThumbSlides().eq(visibleIndex);
      if ($visibleThumb.length) {
        $visibleThumb.addClass("swiper-slide-thumb-active");
      }
    }

    function getButtonByColor(color) {
      var normalized = normalizeColor(color);
      return $colorButtons.filter(function () {
        return normalizeColor($(this).data("color")) === normalized;
      }).first();
    }

    function applyColorFilter(color) {
      var normalized = normalizeColor(color);
      if (!normalized) return;

      $mainSlides.each(function () {
        var isMatch = normalizeColor($(this).data("color")) === normalized;
        $(this).css("display", isMatch ? "" : "none");
      });

      $thumbSlides.each(function () {
        var isMatch = normalizeColor($(this).data("color")) === normalized;
        $(this).css("display", isMatch ? "" : "none");
      });

      main.update();
      thumbs.update();
      main.slideTo(0, 0, false);
      thumbs.slideTo(0, 0, false);
      syncThumbActiveByVisibleIndex(0);
      updateActiveColorButton(0);
    }

    var thumbs = new Swiper(".tf-product-media-thumbs", {
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      direction: "vertical",
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      breakpoints: {
        0: {
          direction: "horizontal",
          slidesPerView: 5,
        },
        1150: {
          direction: "vertical",
          direction: direction,
        },
      },
      450: {
        direction: "vertical",
      },
    });
    var main = new Swiper(".tf-product-media-main", {
      spaceBetween: 0,
      observer: true,
      observeParents: true,
      navigation: {
        nextEl: ".thumbs-next",
        prevEl: ".thumbs-prev",
      },
      thumbs: {
        swiper: thumbs,
      },
    });
    
    function updateActiveColorButton(activeIndex) {
      $colorButtons.removeClass("active");

      var currentSlide = getVisibleMainSlides().eq(activeIndex);
      if (!currentSlide.length) return;
      var currentColor = currentSlide.data("color");
      var $matchedButton = getButtonByColor(currentColor);
      if ($matchedButton.length) {
        var label = $matchedButton.data("value") || $matchedButton.data("color") || currentColor;
        var colorCode = $matchedButton.data("colorCode") || "";
        $matchedButton.addClass("active");
        $('.value-currentColor').text(label);
        $('.value-currentColorCode').text(colorCode);
        $(".select-currentColor").text(label);
      }
    }
    main.on('slideChange', function () {
      syncThumbActiveByVisibleIndex(this.activeIndex);
      updateActiveColorButton(this.activeIndex);
    });


    $(".color-btn").on("click", function() {
      var color = $(this).data("color");
      
      $colorButtons.removeClass("active");
      $(this).addClass("active");

      applyColorFilter(color);
    });
    var initialColor = $(".color-btn").first().data("color");
    var $checkedColorInput = $("input[name='color1']:checked");
    if ($checkedColorInput.length) {
      var checkedFor = $checkedColorInput.attr("id");
      var $checkedLabel = $("label[for='" + checkedFor + "']");
      if ($checkedLabel.length) initialColor = $checkedLabel.data("color");
    }
    applyColorFilter(initialColor);

    $(".tf-product-media-thumbs").on("click", ".swiper-slide", function () {
      if ($(this).css("display") === "none") return;
      var visibleIndex = getVisibleThumbSlides().index($(this));
      if (visibleIndex < 0) return;
      main.slideTo(visibleIndex, 400, false);
      thumbs.slideTo(visibleIndex, 400, false);
      syncThumbActiveByVisibleIndex(visibleIndex);
    });
}

if ($(".thumbs-slider1").length > 0) {
    var direction = $(".tf-product-media-thumbs").data("direction");
    var thumbs = new Swiper(".tf-product-media-thumbs", {
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      direction: "vertical",
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      breakpoints: {
        0: {
          direction: "horizontal",
          slidesPerView: 5,
        },
        1150: {
          direction: "vertical",
          direction: direction,
        },
      },
      450: {
        direction: "vertical",
      },
    });
    var main = new Swiper(".tf-product-media-main1", {
      spaceBetween: 0,
      observer: true,
      observeParents: true,
      navigation: {
        nextEl: ".thumbs-next",
        prevEl: ".thumbs-prev",
      },
      thumbs: {
        swiper: thumbs,
      },
    });
}
if ($(".thumbs-slider2").length > 0) {
    var direction = $(".tf-product-media-thumbs").data("direction");
    var thumbs = new Swiper(".tf-product-media-thumbs", {
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      direction: "vertical",
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      breakpoints: {
        0: {
          direction: "horizontal",
          slidesPerView: 5,
        },
        1150: {
          direction: "vertical",
          direction: direction,
        },
      },
      450: {
        direction: "vertical",
      },
    });
    var main = new Swiper(".tf-product-media-main2", {
      spaceBetween: 0,
      observer: true,
      observeParents: true,
      navigation: {
        nextEl: ".thumbs-next",
        prevEl: ".thumbs-prev",
      },
      thumbs: {
        swiper: thumbs,
      },
    });
}
if ($(".thumbs-slider3").length > 0) {
    var direction = $(".tf-product-media-thumbs").data("direction");
    var thumbs = new Swiper(".tf-product-media-thumbs", {
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      direction: "vertical",
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      breakpoints: {
        0: {
          direction: "horizontal",
          slidesPerView: 5,
        },
        1150: {
          direction: "vertical",
          direction: direction,
        },
      },
      450: {
        direction: "vertical",
      },
    });
    var main = new Swiper(".tf-product-media-main3", {
      spaceBetween: 0,
      observer: true,
      observeParents: true,
      navigation: {
        nextEl: ".thumbs-next",
        prevEl: ".thumbs-prev",
      },
      thumbs: {
        swiper: thumbs,
      },
    });
}
if ($(".thumbs-slider4").length > 0) {
    var direction = $(".tf-product-media-thumbs").data("direction");
    var thumbs = new Swiper(".tf-product-media-thumbs", {
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      direction: "vertical",
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      breakpoints: {
        0: {
          direction: "horizontal",
          slidesPerView: 5,
        },
        1150: {
          direction: "vertical",
          direction: direction,
        },
      },
      450: {
        direction: "vertical",
      },
    });
    var main = new Swiper(".tf-product-media-main4", {
      spaceBetween: 0,
      observer: true,
      observeParents: true,
      navigation: {
        nextEl: ".thumbs-next",
        prevEl: ".thumbs-prev",
      },
      thumbs: {
        swiper: thumbs,
      },
    });
}

(function ($) {
    "use strict";

    var section_zoom = function () {
        $(".tf-image-zoom").on("mouseover", function () {
            $(this).closest(".section-image-zoom").addClass("zoom-active");
        });
        $(".tf-image-zoom").on("mouseleave", function () {
            $(this).closest(".section-image-zoom").removeClass("zoom-active");
        });
    }

    var image_zoom = function () {
        var driftAll = document.querySelectorAll('.tf-image-zoom');
        var pane = document.querySelector('.tf-zoom-main');
        $(driftAll).each(function(i, el) {
            new Drift(
                el, {
                zoomFactor: 2,
                paneContainer: pane,
                inlinePane: false,
                handleTouch: false,
                hoverBoundingBox: true,
                containInline: true,
                }
            );
        });
    }

    var image_zoom_magnifier = function () {
        var driftAll = document.querySelectorAll('.tf-image-zoom-magnifier');
        $(driftAll).each(function(i, el) {
            new Drift(
                el, {
                zoomFactor: 2,
                inlinePane: true,
                containInline: false,
                }
            );
        });
    }

    var image_zoom_inner = function () {
        var driftAll = document.querySelectorAll('.tf-image-zoom-inner');
        var pane = document.querySelector('.tf-product-zoom-inner');
        $(driftAll).each(function(i, el) {
            new Drift(
                el, {
                paneContainer: pane,
                zoomFactor: 2,
                inlinePane: false,
                containInline: false,
                }
            );
        });
    }

    var lightboxswiper = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-swiper-started',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

        lightbox.on('change', () => {
            const { pswp } = lightbox;
            main.slideTo(pswp.currIndex, 0, false);
        });

        lightbox.on('afterInit', () => {
            if (main.params.autoplay.enabled) {
                main.autoplay.stop();
            };
        });

        lightbox.on('closingAnimationStart', () => {
            const { pswp } = lightbox;
            main.slideTo(pswp.currIndex, 0, false);
            if (main.params.autoplay.enabled) {
                main.autoplay.start();
            }
        });

    }
    var lightboxswiper1 = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-swiper-started1',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

    }
    var lightboxswiper2 = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-swiper-started2',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

    }
    var lightboxswiper3 = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-swiper-started3',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

    }
    var lightboxswiper4 = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-swiper-started4',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

    }

    
    var lightbox = function () {

        const lightbox = new PhotoSwipeLightbox({
            gallery: '#gallery-started',
            children: 'a',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3,
        });
        lightbox.init();

    }

    var model_viewer = function () {

        if ($(".tf-model-viewer").length) {
   
            $(".tf-model-viewer-ui-button").on("click", function (e) {
                $(this).closest(".tf-model-viewer").find("model-viewer").removeClass("disabled");
                $(this).closest(".tf-model-viewer").toggleClass("active");
            });
            $(".tf-model-viewer-ui").on("dblclick", function (e) {
                $(this).closest(".tf-model-viewer").find("model-viewer").addClass("disabled");
                $(this).closest(".tf-model-viewer").toggleClass("active");
            });
        }

    }

  // Dom Ready
  $(function () {
    // Disabled product hover zoom behavior.

    lightboxswiper();
    lightboxswiper1();
    lightboxswiper2();
    lightboxswiper3();
    lightboxswiper4();
    
    lightbox();
    model_viewer();
  });
})(jQuery);
