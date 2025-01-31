jQuery(document).ready(function ($) {
  const defaults = {
    bg_color: "#ffffff",
    text_color: "#000000",
    animation_speed: "1s",
    type: "image",
    layout: "image-over-text",
    display_mode: "full",
  };

  function getSafeValue(selector, fallback) {
    const element = $(selector);
    return element.length ? element.val() : fallback;
  }

  // Update preview function
  function updatePreview() {
    const data = {
      type: getSafeValue("#preloader_type", defaults.type),
      image: getSafeValue("#advanced_preloader_image", ""),
      text: getSafeValue("#advanced_preloader_text", "Loading..."),
      layout: getSafeValue("#layout_order", defaults.layout),
      bg_color: getSafeValue(
        'input[name="advanced_preloader_design[bg_color]"]',
        defaults.bg_color
      ),
      text_color: getSafeValue(
        'input[name="advanced_preloader_design[text_color]"]',
        defaults.text_color
      ),
      animation_speed: getSafeValue(
        'input[name="advanced_preloader_animation[animation_speed]"]',
        defaults.animation_speed
      ),
      display_mode: getSafeValue("#text_display_mode", defaults.display_mode),
    };

    // Get random line if needed
    let displayText = data.text;
    if (data.display_mode === "random") {
      const lines = data.text.split("\n").filter((line) => line.trim() !== "");
      displayText =
        lines[Math.floor(Math.random() * lines.length)] || data.text;
    }

    // Update preview content
    $("#preloader-preview").html(`
            <div class="preloader-preview" 
                 style="background-color: ${data.bg_color}; 
                        color: ${data.text_color};
                        animation-duration: ${
                          data.animation_speed
                        }"><div class="preview-inner  ${data.layout}">
                ${
                  data.type !== "text" && data.image
                    ? `<img src="${data.image}" alt="Preview" />`
                    : ""
                }
                ${
                  data.type !== "image"
                    ? `<div class="preloader-text">${displayText}</div></div>`
                    : ""
                }
            </div>
        `);

    // Adjust scaling
    const laptopWidth = $(".laptop-screen").width();
    const scaleFactor = laptopWidth / 1920; // 1920 is our reference size
    $(".preview-inner").css({
      transform: `scale(${scaleFactor})`,
    });
  }
  $(document).on("click", ".nav-tab", function () {
    setTimeout(updatePreview, 100); // Wait for tab content to render
  });

  // Watch for changes
  const watchedElements = [
    "#preloader_type",
    "#advanced_preloader_image",
    "#advanced_preloader_text",
    "#layout_order",
    'input[name="advanced_preloader_design[bg_color]"]',
    'input[name="advanced_preloader_design[text_color]"]',
    'input[name="advanced_preloader_animation[animation_speed]"]',
    "#text_display_mode",
  ];

  watchedElements.forEach((selector) => {
    $(document).on("change input", selector, updatePreview);
  });

  // Handle window resize
  $(window).resize(function () {
    updatePreview();
  });

  // Initial update
  updatePreview();
});
