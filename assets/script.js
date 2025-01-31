jQuery(window).on("load", function () {
  var preloader = jQuery("#advanced-preloader");

  var delayTime = preloader.data("delay") || "0s";
  var animationSpeed = preloader.data("animation-speed") || "slow";

  var delayMs =
    parseFloat(delayTime) * (delayTime.indexOf("ms") > -1 ? 1 : 1000);

  setTimeout(function () {
    preloader.fadeOut(animationSpeed, function () {
      jQuery(this).remove();
    });
  }, delayMs);
});
