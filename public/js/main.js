/**
 * selectImages
 * preloader
 * Scroll process
 * Button Quantity
 * Delete file
 * Go Top
 * color swatch product
 * change value
 * footer accordion
 * close announcement bar
 * sidebar mobile
 * tabs
 * flatAccordion
 * button wishlist
 * button loading
 * variant picker
 * switch layout
 * item checkbox
 * infinite scroll
 * stagger wrap
 * filter
 * modal second
 * header sticky
 * header change background
 * img group
 * contact form
 * subscribe mailchimp
 * auto popup
 * RTL

 */


(function ($) {
  "use strict";

  var isMobile = {
    Android: function () {
      return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function () {
      return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function () {
      return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function () {
      return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function () {
      return navigator.userAgent.match(/IEMobile/i);
    },
    any: function () {
      return (
        isMobile.Android() ||
        isMobile.BlackBerry() ||
        isMobile.iOS() ||
        isMobile.Opera() ||
        isMobile.Windows()
      );
    },
  };

  /* selectImages
  -------------------------------------------------------------------------------------*/
  var selectImages = function () {
    if ($(".image-select").length > 0) {
      const selectIMG = $(".image-select");
      selectIMG.find("option").each((idx, elem) => {
        const selectOption = $(elem);
        const imgURL = selectOption.attr("data-thumbnail");
        if (imgURL) {
          selectOption.attr(
            "data-content",
            "<img src='%i'/> %s"
              .replace(/%i/, imgURL)
              .replace(/%s/, selectOption.text())
          );
        }
      });
      selectIMG.selectpicker();
    }
  };

  /* preloader
  -------------------------------------------------------------------------------------*/
  const preloader = function () {
    if ($("body").hasClass("preload-wrapper")) {
      var $preload = $(".preload");
      if (!$preload.length) return;

      setTimeout(function () {
        $preload.stop(true, true).fadeOut("slow");
      }, 100);
    }
  };

  /* page transition loader
  -------------------------------------------------------------------------------------*/
  var pageTransitionLoader = function () {
    if (!$("body").hasClass("preload-wrapper")) return;

    $(document).on("click", "a[href]", function (event) {
      if (currencySaving) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
      }

      var href = $(this).attr("href");

      if (!href) return;
      if ($(this).attr("target") === "_blank") return;
      if ($(this).attr("download") !== undefined) return;
      if (href.indexOf("#") === 0) return;
      if (href.indexOf("javascript:") === 0) return;
      if (href.indexOf("mailto:") === 0 || href.indexOf("tel:") === 0) return;

      var linkUrl;
      try {
        linkUrl = new URL(href, window.location.href);
      } catch (error) {
        return;
      }

      if (linkUrl.origin !== window.location.origin) return;
      if (linkUrl.href === window.location.href) return;

      $(".preload").stop(true, true).fadeIn(120);
    });
  };

  
  /* country selector
  -------------------------------------------------------------------------------------*/
  var countrySelector = function () {
    if (!$(".js-country-select").length) return;

    $(".js-country-select").on("changed.bs.select change", function (event) {
      if (currencySaving) {
        event.preventDefault();
        return;
      }

      var targetUrl = $(this).val();
      if (!targetUrl) return;
      window.location.href = targetUrl;
    });
  };

  /* language selector
  -------------------------------------------------------------------------------------*/
  var languageSelector = function () {
    if (!$(".js-language-select").length) return;

    $(".js-language-select").on("changed.bs.select change", function (event) {
      if (currencySaving) {
        event.preventDefault();
        return;
      }

      var targetUrl = $(this).val();
      if (!targetUrl) return;
      window.location.href = targetUrl;
    });
  };

  var currencySaving = false;
  var confirmedCurrency = "";

  var getSelectedCurrencyContext = function () {
    var $select = $(".js-currency-select");
    var currency = confirmedCurrency || $select.data("confirmed-currency") || $select.find("option:selected").val() || $select.val() || "";
    var $option = $select.find('option[value="' + currency + '"]').first();

    if (!$option.length) {
      $option = $select.find("option:selected");
    }

    var rate = parseFloat($option.data("rate"));
    var symbol = $option.data("symbol") || currency || "";

    if (!rate || rate <= 0) {
      rate = 1;
    }

    return {
      currency: currency,
      rate: rate,
      symbol: symbol,
    };
  };

  var formatConvertedMoney = function (amount, currency, symbol) {
    var value = Number(amount || 0);
    var rounded = Math.round(value);
    var decimals = Math.abs(value - rounded) < 0.005 ? 0 : 2;
    var formatted = value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: 2,
    });
    var prefix = symbol || currency || "";

    return prefix ? prefix + " " + formatted : formatted;
  };

  var convertBasePrice = function (basePrice, rate) {
    var price = parseFloat(basePrice);
    if (!price || price < 0) {
      price = 0;
    }

    var currencyRate = parseFloat(rate);
    if (!currencyRate || currencyRate <= 0) {
      currencyRate = 1;
    }

    return price / currencyRate;
  };

  var updateCurrencyConvertedPrices = function () {
    if (!$(".js-currency-select").length) return;

    var context = getSelectedCurrencyContext();

    $(".js-currency-price").each(function () {
      var $price = $(this);
      var basePrice = parseFloat($price.attr("data-base-price"));

      if (!basePrice && basePrice !== 0) {
        var rawPrice = String($price.text() || "").replace(/[^\d.]/g, "");
        basePrice = parseFloat(rawPrice) || 0;
        $price.attr("data-base-price", basePrice);
      }

      $price.text(formatConvertedMoney(convertBasePrice(basePrice, context.rate), context.currency, context.symbol));
    });
  };

  window.updateCurrencyConvertedPrices = updateCurrencyConvertedPrices;

  var refreshCurrencyFragments = function (response) {
    if (!response) {
      return;
    }

    if (response.cart_html) {
      var $fragment = $("<div>").html(response.cart_html || "");
      var $newModal = $fragment.find("#shoppingCart");
      var $currentModal = $("#shoppingCart");

      if ($newModal.length) {
        if ($currentModal.length) {
          var $currentItems = $currentModal.find("[data-cart-items]");
          var $currentSubtotal = $currentModal.find("[data-cart-subtotal]");
          var $newItems = $newModal.find("[data-cart-items]").first();
          var $newSubtotal = $newModal.find("[data-cart-subtotal]").first();

          if ($currentItems.length && $newItems.length) {
            $currentItems.replaceWith($newItems);
          }

          if ($currentSubtotal.length && $newSubtotal.length) {
            $currentSubtotal.replaceWith($newSubtotal);
          }
        } else {
          $("body").append($newModal);
        }
      }
    }

    var count = (response.cart_state && response.cart_state.count) || 0;
    $("[data-cart-count]").text(count);

    if (window.updateCurrencyConvertedPrices) {
      window.updateCurrencyConvertedPrices();
    }
  };

  window.refreshCurrencyFragments = refreshCurrencyFragments;

  /* currency selector
  -------------------------------------------------------------------------------------*/
  var currencySelector = function () {
    if (!$(".js-currency-select").length) return;

    var $select = $(".js-currency-select");
    confirmedCurrency = $select.data("confirmed-currency") || $select.find("option:selected").val() || $select.val() || "";
    $select.data("confirmed-currency", confirmedCurrency);

    if ($.fn.selectpicker) {
      $select.selectpicker("refresh");
    }

    $(document).on("changed.bs.select change", ".js-currency-select", function () {
      var $field = $(this);
      var $form = $field.closest("form");
      var nextCurrency = $field.val() || "";
      var previousCurrency = confirmedCurrency || $field.data("confirmed-currency") || "";

      if (!nextCurrency || currencySaving || nextCurrency === confirmedCurrency) {
        return;
      }

      currencySaving = true;
      $form.addClass("is-saving");
      var payload = $form.serialize();
      $field.prop("disabled", true);

      $.ajax({
        url: $form.attr("action") || "",
        type: "POST",
        data: payload,
        dataType: "json",
        xhrFields: {
          withCredentials: true,
        },
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") || "",
          Accept: "application/json",
        },
      }).done(function (response) {
        if (!response || response.success !== true) {
          return;
        }

        confirmedCurrency = response.currency || nextCurrency;
        $field.data("confirmed-currency", confirmedCurrency);
        $field.val(confirmedCurrency);

        if ($.fn.selectpicker) {
          $field.selectpicker("refresh");
        }

        refreshCurrencyFragments(response);
      }).fail(function () {
        confirmedCurrency = previousCurrency;
        $field.data("confirmed-currency", previousCurrency);
        $field.val(previousCurrency);

        if ($.fn.selectpicker) {
          $field.selectpicker("refresh");
        }
      }).always(function () {
        currencySaving = false;
        $form.removeClass("is-saving");
        $field.prop("disabled", false);
        updateCurrencyConvertedPrices();
      });
    });

    updateCurrencyConvertedPrices();
  };

  /* Scroll process
  -------------------------------------------------------------------------------------*/
  var scrollProgress = function () {
    $(".scroll-snap").on("scroll", function () {
      var val = $(this).scrollLeft();
      $(".value-process").css("width", `max(30%,${val}%)`);
    });
  };

  /* Button Quantity
  -------------------------------------------------------------------------------------*/
  var btnQuantity = function () {
    $(".minus-btn").on("click", function (e) {
      e.preventDefault();
      var $this = $(this);
      var $input = $this.closest("div").find("input");
      var value = parseInt($input.val());

      if (value > 1) {
        value = value - 1;
      }
      $input.val(value);
    });

    $(".plus-btn").on("click", function (e) {
      e.preventDefault();
      var $this = $(this);
      var $input = $this.closest("div").find("input");
      var value = parseInt($input.val());

      if (value > -1) {
        value = value + 1;
      }
      $input.val(value);
    });
  };

  /* Delete file 
  -------------------------------------------------------------------------------------*/
  var deleteFile = function (e) {
    $(".remove").on("click", function (e) {
      e.preventDefault();
      var $this = $(this);
      $this.closest(".file-delete").remove();
    });

    $('.tf-compapre-button-clear-all').on("click", function (e) {
      $(".tf-compare-item").remove();
    });
    $(".tf-compare-item .icon").on("click", function (e) {
      var $this = $(this);
      $this.closest(".tf-compare-item").remove();
    });
  };

  /* Go Top
  -------------------------------------------------------------------------------------*/
  var goTop = function () {
    if ($("div").hasClass("progress-wrap")) {
      var progressPath = document.querySelector(".progress-wrap path");
      var pathLength = progressPath.getTotalLength();
      progressPath.style.transition = progressPath.style.WebkitTransition =
        "none";
      progressPath.style.strokeDasharray = pathLength + " " + pathLength;
      progressPath.style.strokeDashoffset = pathLength;
      progressPath.getBoundingClientRect();
      progressPath.style.transition = progressPath.style.WebkitTransition =
        "stroke-dashoffset 10ms linear";
      var updateprogress = function () {
        var scroll = $(window).scrollTop();
        var height = $(document).height() - $(window).height();
        var progress = pathLength - (scroll * pathLength) / height;
        progressPath.style.strokeDashoffset = progress;
      };
      updateprogress();
      $(window).scroll(updateprogress);
      var offset = 200;
      var duration = 0;
      jQuery(window).on("scroll", function () {
        if (jQuery(this).scrollTop() > offset) {
          jQuery(".progress-wrap").addClass("active-progress");
        } else {
          jQuery(".progress-wrap").removeClass("active-progress");
        }
      });
      jQuery(".progress-wrap").on("click", function (event) {
        event.preventDefault();
        jQuery("html, body").animate({ scrollTop: 0 }, duration);
        return false;
      });
    }
  };

  /* color swatch product
  -------------------------------------------------------------------------*/
  var swatchColor = function () {
    if ($(".card-product").length > 0) {
      $(".color-swatch").on("click, mouseover", function () {
        var swatchColor = $(this).find("img").attr("src");
        var imgProduct = $(this).closest(".card-product").find(".img-product");
        imgProduct.attr("src", swatchColor);
        $(this)
          .closest(".card-product")
          .find(".color-swatch.active")
          .removeClass("active");

        $(this).addClass("active");
      });
    }
  };

  /* change value
  ------------------------------------------------------------------------------------- */
  var changeValue = function () {
    if ($(".tf-dropdown-sort").length > 0) {
      $(".select-item").click(function (event) {
        $(this)
          .closest(".tf-dropdown-sort")
          .find(".text-sort-value")
          .text($(this).find(".text-value-item").text());

        $(this)
          .closest(".dropdown-menu")
          .find(".select-item.active")
          .removeClass("active");

        $(this).addClass("active");
      });
    }
  };

  /* footer accordion
  -------------------------------------------------------------------------*/
  var footer = function () {
    var args = { duration: 250 };
    $(".footer-heading-moblie").on("click", function () {
      $(this).parent(".footer-col-block").toggleClass("open");
      if (!$(this).parent(".footer-col-block").is(".open")) {
        $(this).next().slideUp(args);
      } else {
        $(this).next().slideDown(args);
      }
    });
  };

  /* close announcement bar
  -------------------------------------------------------------------------*/
  var closeAnnouncement = function () {
    $(".close-announcement-bar").on("click", function (e) {
      e.preventDefault();
      var $this = $(this);
      var $height = $(".announcement-bar").height() + "px";
      $this.closest(".announcement-bar").css("margin-top", `-${$height}`);

      $(".announcement-bar").fadeOut("slow", function () {
        $this.closest(".announcement-bar").remove();
      });
    });
  };

  /* range
  -------------------------------------------------------------------------*/
  var rangePrice = function(){
    const rangeInput = document.querySelectorAll('.range-input input')
    const progress = document.querySelector('.progress-price')
    const minPrice = document.querySelector('.min-price')
    const maxPrice = document.querySelector('.max-price')

    let priceGap = 10

    rangeInput.forEach(input => {
        input.addEventListener('input', e => {
            let minValue = parseInt(rangeInput[0].value, 10)
            let maxValue = parseInt(rangeInput[1].value, 10)

            if (maxValue - minValue < priceGap) {
                if (e.target.class === 'range-min') {
                    rangeInput[0].value = maxValue - priceGap
                } else {
                    rangeInput[1].value = minValue + priceGap
                }
            } else {
                progress.style.left = (minValue / rangeInput[0].max) * 100 + "%";
                progress.style.right = 100 - (maxValue / rangeInput[1].max) * 100 + "%";
            }

            minPrice.innerHTML = minValue
            maxPrice.innerHTML = maxValue

            if (minValue >= 290) {
                minPrice.innerHTML = 290
            }

            if (maxValue <= 10) {
                maxPrice.innerHTML = 10
            }
        })
    })

  }

  /* sidebar mobile
  -------------------------------------------------------------------------*/
  var sidebarMobile = function () {
    if ($(".wrap-sidebar-mobile,.wrap-sidebar-account").length > 0) {
      var sidebar = $(".wrap-sidebar-mobile,.wrap-sidebar-account").html();
      $(".sidebar-mobile-append").append(sidebar);
      // $(".wrap-sidebar-mobile").remove();
    }
  };

  /* tabs
  -------------------------------------------------------------------------*/
  var tabs = function () {
    $(".widget-tabs").each(function () {
      $(this)
        .find(".widget-menu-tab")
        .children(".item-title")
        .on("click", function () {
          var liActive = $(this).index();
          var contentActive = $(this)
            .siblings()
            .removeClass("active")
            .parents(".widget-tabs")
            .find(".widget-content-tab")
            .children()
            .eq(liActive);
          contentActive.addClass("active").fadeIn("slow");
          contentActive.siblings().removeClass("active");
          $(this)
            .addClass("active")
            .parents(".widget-tabs")
            .find(".widget-content-tab")
            .children()
            .eq(liActive);
        });
    });
  };

  /* flatAccordion
  -------------------------------------------------------------------------*/
  var flatAccordion = function (class1, class2) {
    var args = { duration: 200 };

    $(class2 + " .toggle-title.active")
      .siblings(".toggle-content")
      .show();
    $(class1 + " .toggle-title").on("click", function () {
      $(class1 + " " + class2).removeClass("active");
      $(this).closest(class2).toggleClass("active");

      if (!$(this).is(".active")) {
        $(this).toggleClass("active");
        $(this).next().slideToggle(args);
      } else {
        $(class1 + " " + class2).removeClass("active");
        $(this).toggleClass("active");
        $(this).next().slideToggle(args);
      }
    });
  };

  /* button wishlist
  -------------------------------------------------------------------------*/
  var btnWishlist = function () {
    if ($(".btn-icon-action").length) {
      $(".btn-icon-action").on("click", function (e) {
        $(this).toggleClass("active");
      });
    }
  };

  /* button loading
  -------------------------------------------------------------------------*/
  var btnLoading = function () {
    if ($(".tf-btn-loading").length) {
      $(".tf-btn-loading").on("click", function (e) {
        $(this).addClass("loading");
        var $this = $(this);
        setTimeout(function () {
          $this.removeClass("loading");
        }, 600);
      });
    }
  };

  /* variant picker
  -------------------------------------------------------------------------*/
  var variantPicker = function () {
    if ($(".variant-picker-item").length) {
      $(".variant-picker-item label").on("click", function (e) {
        $(this)
          .closest(".variant-picker-item")
          .find(".variant-picker-label-value")
          .text($(this).data("value"));

        var colorCode = $(this).data("colorCode");
        if (colorCode !== undefined) {
          $(".value-currentColorCode").text(colorCode);
        }
      });
    }
    if ($(".variant-picker-item").length) {
      $(".select-size").on("click", function (e) {
        $(this)
          .closest(".variant-picker-item")
          .find(".variant-picker-label-value")
          .text($(this).data("value"));
      });
    }
  };

  /* switch layout
  -------------------------------------------------------------------------*/
  var switchLayout = function () {
    if ($(".tf-control-layout").length > 0) {
      $(".tf-view-layout-switch").on("click", function () {
        var value = $(this).data("value-grid");
        $(".grid-layout").attr("data-grid", value);
        $(this)
          .closest(".tf-control-layout")
          .find(".tf-view-layout-switch.active")
          .removeClass("active");

        $(this).addClass("active");
      });
      if (matchMedia("only screen and (max-width: 1150px)").matches) {
        $(".tf-view-layout-switch.active").removeClass("active");
        $(".sw-layout-3").addClass("active");
      }
      if (matchMedia("only screen and (max-width: 768px)").matches) {
        $(".tf-view-layout-switch.active").removeClass("active");
        $(".sw-layout-2").addClass("active");
      }
    }
  };

  /* item checkbox
  -------------------------------------------------------------------------*/
  var itemCheckbox = function () {
    if ($(".item-has-checkox").length) {
      $(".item-has-checkox input:checkbox").on("click", function (e) {
        $(this).closest(".item-has-checkox").toggleClass("check");
      });
    }
  };

  /* infinite scroll
  -------------------------------------------------------------------------*/
  var infiniteScroll = function () {
    $(".fl-item").slice(0, 8).show();
    $(".fl-item2").slice(0, 8).show();
    $(".fl-item3").slice(0, 8).show();

    if ($(".scroll-loadmore").length > 0) {
      $(window).scroll(function () {
        if (
          $(window).scrollTop() >=
          $(document).height() - $(window).height()
        ) {
          setTimeout(() => {
            $(".fl-item:hidden").slice(0, 4).show();
            if ($(".fl-item:hidden").length == 0) {
              $(".view-more-button").hide();
            }
          }, 0);
        }
      });
    }
    if ($(".loadmore-item").length > 0) {
      $(".btn-loadmore").on("click", function () {
        setTimeout(() => {
          $(".fl-item:hidden").slice(0, 4).show();
          if ($(".fl-item:hidden").length == 0) {
            $(".view-more-button").hide();
          }
        }, 600);
      });
    }
    if ($(".loadmore-item2").length > 0) {
      $(".btn-loadmore2").on("click", function () {
        setTimeout(() => {
          $(".fl-item2:hidden").slice(0, 4).show();
          if ($(".fl-item2:hidden").length == 0) {
            $(".view-more-button2").hide();
          }
        }, 600);
      });
    }
    if ($(".loadmore-item3").length > 0) {
      $(".btn-loadmore3").on("click", function () {
        setTimeout(() => {
          $(".fl-item3:hidden").slice(0, 4).show();
          if ($(".fl-item3:hidden").length == 0) {
            $(".view-more-button3").hide();
          }
        }, 600);
      });
    }
  };
  /* stagger wrap
  -------------------------------------------------------------------------*/
  var staggerWrap = function () {
    if ($(".stagger-wrap").length) {
      var count = $(".stagger-item").length;
      // $(".stagger-item").addClass("stagger-finished");
      for (var i = 1, time = 0.2; i <= count; i++) {
        $(".stagger-item:nth-child(" + i + ")")
          .css("transition-delay", time * i + "s")
          .addClass("stagger-finished");
      }
    }
  };

  /* filter
  -------------------------------------------------------------------------*/
  var filterTab = function () {
    var $btnFilter = $('.tf-btns-filter').click(function() {
      if (this.id == 'all') {
        $('#parent > div').show();
      } else {
        var $el = $('.' + this.id).show();
        $('#parent > div').not($el).hide();
      }
      $btnFilter.removeClass('is--active');
      $(this).addClass('is--active');
    })
  };

  /* modal second
  -------------------------------------------------------------------------*/
  var clickModalSecond = function () {
    $(".btn-choose-size").click(function () {
      $("#find_size").modal("show");
    });
    $(".btn-show-quickview").click(function () {
      $("#quick_view").modal("show");
    });
    $(".btn-add-to-cart").click(function () {
      if ($(this).is("[data-cart-submit]")) {
        return;
      }
      $("#shoppingCart").modal("show");
    });

    $(".btn-add-note").click(function () {
      $(".add-note").addClass("open");
    });
    $(".btn-add-gift").click(function () {
      $(".add-gift").addClass("open");
    });
    $(".btn-estimate-shipping").click(function () {
      $(".estimate-shipping").addClass("open");
    });
    $(".tf-mini-cart-tool-close ,.tf-mini-cart-tool-close .overplay").click(
      function () {
        $(".tf-mini-cart-tool-openable").removeClass("open");
      }
    );
  };



  /* header sticky
  -------------------------------------------------------------------------*/
  var headerSticky = function () {
    let didScroll;
    let lastScrollTop = 0;
    let delta = 5;
    let navbarHeight = $("header").outerHeight();
    $(window).scroll(function (event) {
      didScroll = true;
    });
    
    setInterval(function () {
      if (didScroll) {
        let st = $(this).scrollTop();

        // Make scroll more than delta
        if (Math.abs(lastScrollTop - st) <= delta) return;
        // If scrolled down and past the navbar, add class .nav-up.
        if (st > lastScrollTop && st > navbarHeight) {
          // Scroll Down
          $("header").css("top",`-${navbarHeight}px`)
        } else {
          // Scroll Up
          if (st + $(window).height() < $(document).height()) {
            $("header").css("top","0px");
          }
        }
        lastScrollTop = st;
        didScroll = false;
      }
    }, 250);
  };

  /* header change background
  -------------------------------------------------------------------------*/
  var headerChangeBg = function () {
    $(window).on("scroll", function () {
      if ($(window).scrollTop() > 100) {
        $("header").addClass("header-bg");
      } else {
        $("header").removeClass("header-bg");
      }
    });
  }
   /* total cart
  -------------------------------------------------------------------------*/
  var totalPriceVariant = function () {
    var context = getSelectedCurrencyContext();
    var basePrice = parseFloat($(".price-on-sale").data("base-price")) || parseFloat($(".price-on-sale").text().replace(/[^\d.]/g, ""));
    var quantityInput = $(".quantity-product");
    // quantityInput.on("keydown keypress input", function(event) {
    //   event.preventDefault();
    // });
    $(".color-btn, .size-btn").on("click", function () {
      var newPrice = parseFloat($(this).data("price")) || basePrice;
      quantityInput.val(1);
      $(".price-on-sale").attr("data-base-price", newPrice);
      $(".price-on-sale").text(formatConvertedMoney(convertBasePrice(newPrice, context.rate), context.currency, context.symbol));
      var totalPrice = newPrice;
      $(".total-price").attr("data-base-price", totalPrice);
      $(".total-price").text(formatConvertedMoney(convertBasePrice(totalPrice, context.rate), context.currency, context.symbol));
    });

    $(".btn-increase").on("click", function () {
      var currentQuantity = parseInt(quantityInput.val());
      quantityInput.val(currentQuantity + 1);
      updateTotalPrice();
    });

    $(".btn-decrease").on("click", function () {
      var currentQuantity = parseInt(quantityInput.val());
      if (currentQuantity > 1) {
        quantityInput.val(currentQuantity - 1);
        updateTotalPrice();
      }
    });

    function updateTotalPrice() {
      var currentPrice = parseFloat($(".price-on-sale").attr("data-base-price")) || basePrice;
      var quantity = parseInt(quantityInput.val());
      var totalPrice = currentPrice * quantity;
      $(".total-price").attr("data-base-price", totalPrice);
      $(".total-price").text(formatConvertedMoney(convertBasePrice(totalPrice, context.rate), context.currency, context.symbol));
    }

  };

  /* cart line totals
  -------------------------------------------------------------------------*/
  var cartLineTotals = function () {
    function parseMoney(text) {
      return parseInt(String(text || "").replace(/[^\d]/g, ""), 10) || 0;
    }

    function formatMoney(value, currency) {
      var context = getSelectedCurrencyContext();
      return formatConvertedMoney(value, currency || context.currency, context.symbol);
    }

    function currentRate() {
      return getSelectedCurrencyContext().rate;
    }

    function csrfToken() {
      return $('meta[name="csrf-token"]').attr("content") || "";
    }

    function syncCartState(response) {
      if (window.refreshCurrencyFragments) {
        window.refreshCurrencyFragments(response);
      }
    }

    function requestCart(url, method, data) {
      return $.ajax({
        url: url,
        type: method,
        data: data || {},
        dataType: "json",
        headers: {
          "X-CSRF-TOKEN": csrfToken(),
          Accept: "application/json",
        },
      });
    }

    function updateCartRow($row) {
      var unitPrice =
        parseInt($row.attr("data-unit-price"), 10) ||
        parseMoney($row.find(".cart-price").text());
      $row.attr("data-unit-price", unitPrice);

      var qty = parseInt($row.find("input[name='number']").val(), 10);
      if (!qty || qty < 1) qty = 1;
      $row.find("input[name='number']").val(qty);

      $row.find(".cart-total").text(formatMoney(unitPrice * qty));
    }

    function updateCartSubtotal() {
      var subtotal = 0;
      $(".tf-table-page-cart .tf-cart-item").each(function () {
        subtotal += parseMoney($(this).find(".cart-total").text());
      });
      $(".tf-page-cart-footer .total-value").text(formatMoney(subtotal));
    }

    function updateMiniCartItem($item) {
      var unitPrice =
        parseFloat($item.attr("data-base-price")) ||
        parseFloat($item.find(".price").first().attr("data-base-price")) ||
        parseMoney($item.find(".price").first().text());
      $item.attr("data-unit-price", unitPrice);
      $item.attr("data-base-price", unitPrice);

      var qty = parseInt($item.find("input[name='number']").val(), 10);
      if (!qty || qty < 1) qty = 1;
      $item.find("input[name='number']").val(qty);

      $item.find(".price").first().attr("data-base-price", unitPrice * qty);
      $item.find(".price").first().text(formatMoney(convertBasePrice(unitPrice * qty, currentRate())));
    }

    function updateMiniCartSubtotal() {
      var subtotal = 0;
      $(".modal-shopping-cart .tf-mini-cart-item").each(function () {
        var $item = $(this);
        var unitPrice =
          parseFloat($item.attr("data-base-price")) ||
          parseFloat($item.find(".price").first().attr("data-base-price")) ||
          parseMoney($item.find(".price").first().text());
        var qty = parseInt($item.find("input[name='number']").val(), 10);
        if (!qty || qty < 1) qty = 1;
        subtotal += unitPrice * qty;
      });
      $(".modal-shopping-cart .tf-totals-total-value")
        .attr("data-base-price", subtotal)
        .text(formatMoney(convertBasePrice(subtotal, currentRate())));
    }

    $(".tf-table-page-cart .tf-cart-item").each(function () {
      updateCartRow($(this));
    });
    updateCartSubtotal();

    $(".modal-shopping-cart .tf-mini-cart-item").each(function () {
      updateMiniCartItem($(this));
    });
    updateMiniCartSubtotal();

    $(document).on("click", ".tf-table-page-cart .btnincrease", function () {
      var $row = $(this).closest(".tf-cart-item");
      var $input = $row.find("input[name='number']");
      $input.val((parseInt($input.val(), 10) || 1) + 1);
      updateCartRow($row);
      updateCartSubtotal();
    });

    $(document).on("click", ".tf-table-page-cart .btndecrease", function () {
      var $row = $(this).closest(".tf-cart-item");
      var $input = $row.find("input[name='number']");
      var qty = parseInt($input.val(), 10) || 1;
      $input.val(Math.max(1, qty - 1));
      updateCartRow($row);
      updateCartSubtotal();
    });

    $(document).on("input change", ".tf-table-page-cart input[name='number']", function () {
      var $row = $(this).closest(".tf-cart-item");
      updateCartRow($row);
      updateCartSubtotal();
    });

    $(document).on("click", ".modal-shopping-cart .plus-btn", function () {
      var $item = $(this).closest(".tf-mini-cart-item");
      var updateUrl = $item.data("cartUpdateUrl");
      var $input = $item.find("input[name='number']");
      var qty = (parseInt($input.val(), 10) || 1) + 1;

      if (updateUrl) {
        requestCart(updateUrl, "PATCH", { quantity: qty }).done(syncCartState);
        return;
      }

      $input.val(qty);
      updateMiniCartItem($item);
      updateMiniCartSubtotal();
    });

    $(document).on("click", ".modal-shopping-cart .minus-btn", function () {
      var $item = $(this).closest(".tf-mini-cart-item");
      var updateUrl = $item.data("cartUpdateUrl");
      var $input = $item.find("input[name='number']");
      var qty = parseInt($input.val(), 10) || 1;
      qty = Math.max(1, qty - 1);

      if (updateUrl) {
        requestCart(updateUrl, "PATCH", { quantity: qty }).done(syncCartState);
        return;
      }

      $input.val(qty);
      updateMiniCartItem($item);
      updateMiniCartSubtotal();
    });

    $(document).on("input change", ".modal-shopping-cart input[name='number']", function () {
      var $item = $(this).closest(".tf-mini-cart-item");
      var updateUrl = $item.data("cartUpdateUrl");
      var qty = parseInt($(this).val(), 10) || 1;

      if (updateUrl) {
        requestCart(updateUrl, "PATCH", { quantity: qty }).done(syncCartState);
        return;
      }

      updateMiniCartItem($item);
      updateMiniCartSubtotal();
    });

    $(document).on("click", ".tf-table-page-cart .remove", function () {
      setTimeout(updateCartSubtotal, 0);
    });

    $(document).on("click", ".modal-shopping-cart .tf-mini-cart-remove", function () {
      var $item = $(this).closest(".tf-mini-cart-item");
      var removeUrl = $item.data("cartRemoveUrl");

      if (removeUrl) {
        requestCart(removeUrl, "DELETE").done(syncCartState);
        return;
      }

      setTimeout(updateMiniCartSubtotal, 0);
    });

    updateMiniCartSubtotal();
  };

  /* scroll grid product
  ------------------------------------------------------------------------------------- */
  var scrollGridProduct = function(){

    var headerHeight = $("#header").outerHeight(); 
    var activeColorBtn = null; 
    $(".btn-grid-color").on("click", function () {
        var color = $(this).data("color");
        var target = $(".item-img-color[data-color='" + color + "']"); 
        $('html, body').animate({
            scrollTop: target.offset().top - headerHeight 
        }, 100);

        $(".btn-grid-color").removeClass("active");
        $(this).addClass("active");
        activeColorBtn = $(this); 
    });

    $(window).on("scroll", function () {
        var isActiveSet = false; 
        $(".item-img-color").each(function () {
            var targetTop = $(this).offset().top - headerHeight;
            if ($(window).scrollTop() >= targetTop && $(window).scrollTop() < (targetTop + $(this).outerHeight())) {
                var color = $(this).data("color");
                if (!isActiveSet && (activeColorBtn === null || activeColorBtn.data("color") !== color)) {
                    $(".btn-grid-color").removeClass("active");
                    $(".btn-grid-color[data-color='" + color + "']").addClass("active");
                    // $('.value-currentColor').text(color);
                }
                isActiveSet = true; 
            }
        });
        if (!isActiveSet && activeColorBtn !== null) {
            $(".btn-grid-color").removeClass("active");
            activeColorBtn.addClass("active");
        }
    });
  }

  /* contact form
  ------------------------------------------------------------------------------------- */
  var ajaxContactForm = function () {
    $("#contactform").each(function () {
      $(this).validate({
        submitHandler: function (form) {
          var $form = $(form),
            str = $form.serialize(),
            loading = $("<div />", { class: "loading" });

          $.ajax({
            type: "POST",
            url: $form.attr("action"),
            data: str,
            beforeSend: function () {
              $form.find(".send-wrap").append(loading);
            },
            success: function (msg) {
              var result, cls;
              if (msg == "Success") {
                result =
                  "Email Sent Successfully. Thank you, Your application is accepted - we will contact you shortly";
                cls = "msg-success";
              } else {
                result = "Error sending email.";
                cls = "msg-error";
              }
              $form.prepend(
                $("<div />", {
                  class: "flat-alert " + cls,
                  text: result,
                }).append(
                  $(
                    '<a class="close" href="#"><i class="icon icon-close2"></i></a>'
                  )
                )
              );

              $form.find(":input").not(".submit").val("");
            },
            complete: function (xhr, status, error_thrown) {
              $form.find(".loading").remove();
            },
          });
        },
      });
    }); // each contactform
  };
  
  /* subscribe mailchimp
  ------------------------------------------------------------------------------------- */
  var ajaxSubscribe = {
    obj: {
      subscribeEmail: $("#subscribe-email"),
      subscribeButton: $("#subscribe-button"),
      subscribeMsg: $("#subscribe-msg"),
      subscribeContent: $("#subscribe-content"),
      dataMailchimp: $("#subscribe-form").attr("data-mailchimp"),
      success_message:
        '<div class="notification_ok">Thank you for joining our mailing list!</div>',
      failure_message:
        '<div class="notification_error">Error! <strong>There was a problem processing your submission.</strong></div>',
      noticeError: '<div class="notification_error">{msg}</div>',
      noticeInfo: '<div class="notification_error">{msg}</div>',
      basicAction: "mail/subscribe.php",
      mailChimpAction: "mail/subscribe-mailchimp.php",
    },

    eventLoad: function () {
      var objUse = ajaxSubscribe.obj;

      $(objUse.subscribeButton).on("click", function () {
        if (window.ajaxCalling) return;
        var isMailchimp = objUse.dataMailchimp === "true";

        // if (isMailchimp) {
        //   ajaxSubscribe.ajaxCall(objUse.mailChimpAction);
        // } else {
        //   ajaxSubscribe.ajaxCall(objUse.basicAction);
        // }
        ajaxSubscribe.ajaxCall(objUse.basicAction);
      });
    },

    ajaxCall: function (action) {
      window.ajaxCalling = true;
      var objUse = ajaxSubscribe.obj;
      var messageDiv = objUse.subscribeMsg.html("").hide();
      $.ajax({
        url: action,
        type: "POST",
        dataType: "json",
        data: {
          subscribeEmail: objUse.subscribeEmail.val(),
        },
        success: function (responseData, textStatus, jqXHR) {
          if (responseData.status) {
            objUse.subscribeContent.fadeOut(500, function () {
              messageDiv.html(objUse.success_message).fadeIn(500);
            });
          } else {
            switch (responseData.msg) {
              case "email-required":
                messageDiv.html(
                  objUse.noticeError.replace(
                    "{msg}",
                    "Error! <strong>Email is required.</strong>"
                  )
                );
                break;
              case "email-err":
                messageDiv.html(
                  objUse.noticeError.replace(
                    "{msg}",
                    "Error! <strong>Email invalid.</strong>"
                  )
                );
                break;
              case "duplicate":
                messageDiv.html(
                  objUse.noticeError.replace(
                    "{msg}",
                    "Error! <strong>Email is duplicate.</strong>"
                  )
                );
                break;
              case "filewrite":
                messageDiv.html(
                  objUse.noticeInfo.replace(
                    "{msg}",
                    "Error! <strong>Mail list file is open.</strong>"
                  )
                );
                break;
              case "undefined":
                messageDiv.html(
                  objUse.noticeInfo.replace(
                    "{msg}",
                    "Error! <strong>undefined error.</strong>"
                  )
                );
                break;
              case "api-error":
                objUse.subscribeContent.fadeOut(500, function () {
                  messageDiv.html(objUse.failure_message);
                });
            }
            messageDiv.fadeIn(500);
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          alert("Connection error");
        },
        complete: function (data) {
          window.ajaxCalling = false;
        },
      });
    },
  };

  /* auto popup
  ------------------------------------------------------------------------------------- */
  var autoPopup = function () {
    if($("body").hasClass("popup-loader")){
      if ($(".auto-popup").length > 0) {
        let showPopup = sessionStorage.getItem("showPopup");
        if (!JSON.parse(showPopup)) {
          setTimeout(function () {
            $(".auto-popup").modal('show');
          }, 3000);
        }
      }
      $(".btn-hide-popup").on("click", function () {
        sessionStorage.setItem("showPopup", true);
      });
    };

  };
  /* toggle control
  ------------------------------------------------------------------------------------- */
  var clickControl = function () {
    // var args = { duration: 500 };

    $(".btn-address").click(function () {
      $(".show-form-address").toggle();
    });
    $(".btn-hide-address").click(function () {
      $(".show-form-address").hide();
    });

    $(".btn-edit-address").click(function () {
      $(this).closest(".account-address-item").find(".edit-form-address").toggle();
    });
    $(".btn-hide-edit-address").click(function () {
      $(this).closest(".account-address-item").find(".edit-form-address").hide();
    });
  };
  /* RTL
  ------------------------------------------------------------------------------------- */
  var RTL = function () {
    var pageDir = $("html").attr("dir") === "rtl" ? "rtl" : "ltr";
    var storedDir = localStorage.getItem("dir");
    var dir = pageDir === "rtl" ? "rtl" : (storedDir === "rtl" ? "rtl" : "ltr");

    $("html").attr("dir", dir);
    $("body").toggleClass("rtl", dir === "rtl");
    localStorage.setItem("dir", dir);
    $('#toggle-rtl').text(dir === "rtl" ? 'ltr' : 'rtl');

    if (dir === "rtl") {
      $(".tf-slideshow .tf-btn,.view-all-demo .tf-btn, .pagination-link, .pagination-item").find(".icon").removeClass("icon-arrow-right").addClass("icon-arrow-left");
    }

    $("#toggle-rtl").on("click", function() {
      if ($("html").attr("dir") === "rtl") {
        localStorage.setItem("dir", "ltr"); 
        $('#toggle-rtl').text('rtl');      

      } else {
        localStorage.setItem("dir", "rtl");
        $('#toggle-rtl').text('ltr');      
      }
      location.reload();
    });
  };


  /* hoverPin
  -------------------------------------------------------------------------*/
  var hoverPin = function () {
    if ($(".wrap-lookbook-hover").length) {
      $(".bundle-pin-item").on("mouseover", function () {
        $(".bundle-hover-wrap").addClass("has-hover");
        var $el = $('.' + this.id).show();
        $('.bundle-hover-wrap .bundle-hover-item').not($el).addClass("no-hover");
      });
      $(".bundle-pin-item").on("mouseleave", function () {
        $(".bundle-hover-wrap").removeClass("has-hover");
        $(".bundle-hover-item").removeClass("no-hover");
      });
    }
  };

    /* write-review
  ------------------------------------------------------------------------------------- */
  var writeReview = function () {

    if ($(".write-cancel-review-wrap").length > 0) {
      $(".btn-comment-review").click(function () {
        $(this).closest(".write-cancel-review-wrap").toggleClass("write-review");
      });
    }
   
  };




  // Dom Ready
  $(function () {
    selectImages();
    countrySelector();
    languageSelector();
    currencySelector();
    btnQuantity();
    deleteFile();
    goTop();
    closeAnnouncement();
    preloader();
    pageTransitionLoader();
    sidebarMobile();
    tabs();
    flatAccordion(".flat-accordion", ".flat-toggle");
    flatAccordion(".flat-accordion1", ".flat-toggle1");
    swatchColor();
    changeValue();
    footer();
    btnWishlist();
    btnLoading();
    variantPicker();
    switchLayout();
    itemCheckbox();
    infiniteScroll();
    staggerWrap();
    clickModalSecond();
    scrollProgress();
    headerSticky();
    headerChangeBg();
    totalPriceVariant();
    scrollGridProduct();
    filterTab();
    writeReview();
    ajaxContactForm();
    ajaxSubscribe.eventLoad();
    autoPopup();
    rangePrice();
    clickControl();
    RTL();
    hoverPin();
    cartLineTotals();
    new WOW().init();
  });
})(jQuery);





