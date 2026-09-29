jQuery(window).on("load", function () {
  var preloader = jQuery("#advanced-preloader");
  if (!preloader.length) {
    return;
  }

  // Remember the visit for the "once per visit" / "first visit only" rules.
  var frequency = preloader.data("frequency");
  try {
    if (frequency === "session") {
      window.sessionStorage.setItem("advancedPreloaderSeen", "1");
    } else if (frequency === "once") {
      window.localStorage.setItem("advancedPreloaderSeen", "1");
    }
  } catch (e) {
    // Storage can be blocked (private mode, strict privacy settings); just show it every time.
  }

  if (document.documentElement.classList.contains("ap-skip")) {
    preloader.remove();
    return;
  }

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
