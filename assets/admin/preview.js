jQuery(document).ready(function ($) {
  const loaders = (window.advancedPreloaderAdmin && window.advancedPreloaderAdmin.loaders) || {};
  const defaults = {
    bg_color: "#ffffff",
    text_color: "#000000",
    type: "image",
    layout: "image-over-text",
    display_mode: "full",
    loader_style: "spinner",
    loader_size: "medium",
  };

  function getSafeValue(selector, fallback) {
    const element = $(selector);
    const value = element.length ? element.val() : "";
    return value ? value : fallback;
  }

  function updatePreview() {
    const data = {
      type: getSafeValue("#preloader_type", defaults.type),
      image: getSafeValue("#advanced_preloader_image_url", ""),
      text: getSafeValue("#advanced_preloader_text", "Loading..."),
      layout: getSafeValue("#layout_order", defaults.layout),
      bg_color: getSafeValue('input[name="advanced_preloader_design[bg_color]"]', defaults.bg_color),
      text_color: getSafeValue('input[name="advanced_preloader_design[text_color]"]', defaults.text_color),
      loader_color: getSafeValue('input[name="advanced_preloader_design[loader_color]"]', ""),
      loader_size: getSafeValue("#ap_loader_size", defaults.loader_size),
      loader_style:
        $('input[name="advanced_preloader_general[loader_style]"]:checked').val() || defaults.loader_style,
      display_mode: getSafeValue("#text_display_mode", defaults.display_mode),
    };

    const hasLoader = data.type === "loader" || data.type === "loader_text";
    const hasImage = data.type === "image" || data.type === "both";
    const hasText = data.type === "text" || data.type === "both" || data.type === "loader_text";

    let displayText = data.text;
    if (data.display_mode === "random") {
      const lines = data.text.split("\n").filter((line) => line.trim() !== "");
      displayText = lines[Math.floor(Math.random() * lines.length)] || data.text;
    }

    const $inner = $('<div class="preview-inner"></div>').addClass(data.layout);
    if (hasLoader && loaders[data.loader_style]) {
      $inner.append(loaders[data.loader_style]);
    } else if (hasImage && data.image) {
      $inner.append($("<img alt=\"\" />").attr("src", data.image));
    }
    if (hasText) {
      // The text field allows HTML on the site, so the preview renders it the same way.
      $inner.append($('<div class="preloader-text"></div>').html(displayText));
    }

    const $screen = $('<div class="preloader-preview"></div>')
      .addClass("ap-size-" + data.loader_size)
      .css({
        backgroundColor: data.bg_color,
        color: data.text_color,
        "--ap-loader-color": data.loader_color || data.text_color,
      })
      .append($inner);

    $("#preloader-preview").empty().append($screen);

    // Shrink the content to the frame, but keep it large enough to judge the style.
    const scaleFactor = Math.min(1, $(".laptop-screen").width() / 640);
    $(".preview-inner").css({ transform: `scale(${scaleFactor})` });
  }

  $(document).on("click", ".nav-tab", function () {
    setTimeout(updatePreview, 100);
  });

  [
    "#preloader_type",
    "#advanced_preloader_image_url",
    "#advanced_preloader_text",
    "#layout_order",
    'input[name="advanced_preloader_design[bg_color]"]',
    'input[name="advanced_preloader_design[text_color]"]',
    'input[name="advanced_preloader_design[loader_color]"]',
    "#ap_loader_size",
    'input[name="advanced_preloader_general[loader_style]"]',
    "#text_display_mode",
  ].forEach((selector) => {
    $(document).on("change input", selector, updatePreview);
  });

  $(window).on("resize", updatePreview);
  updatePreview();
});
