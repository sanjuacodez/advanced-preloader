jQuery(window).on("load", function () {
  var preloader = jQuery("#advanced-preloader");

  // Both values are milliseconds, converted from the settings on the server.
  var delayMs = parseInt(preloader.data("delay"), 10) || 0;
  var speedMs = parseInt(preloader.data("animation-speed"), 10);
  if (isNaN(speedMs)) {
    speedMs = 1000;
  }

  setTimeout(function () {
    preloader.fadeOut(speedMs, function () {
      jQuery(this).remove();
    });
  }, delayMs);
});
