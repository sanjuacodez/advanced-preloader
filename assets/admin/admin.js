jQuery(document).ready(function ($) {
    // Initialize media uploader variable
    let mediaUploader;

    // Show only the fields that apply to the chosen preloader type
    function toggleFields() {
        const type = $("#preloader_type").val();
        const hasImage = type === "image" || type === "both";
        const hasLoader = type === "loader" || type === "loader_text";
        const hasText = type === "text" || type === "both" || type === "loader_text";

        $("#advanced_preloader_image").closest("tr").toggle(hasImage);
        $("#ap_loader_style").closest("tr").toggle(hasLoader);
        $("#advanced_preloader_text").closest("tr").toggle(hasText);
        $("#text_display_mode").closest("tr").toggle(hasText);
        $("#layout_order_wrapper").closest("tr").toggle(type === "both" || type === "loader_text");
    }

    toggleFields();
    $("#preloader_type").on("change", toggleFields);

    // Loader picker: mirror the checked radio as a class (for browsers without :has())
    function markLoader() {
        $(".ap-loader-option").each(function () {
            $(this).toggleClass("is-selected", $(this).find("input").is(":checked"));
        });
    }
    markLoader();
    $(document).on("change", 'input[name="advanced_preloader_general[loader_style]"]', markLoader);

    // Display Rules: the page list only matters for the "selected pages" rules
    function togglePages() {
        const rule = $('input[name="advanced_preloader_display[show_on]"]:checked').val();
        $("#ap_pages").closest("tr").toggle(rule === "only" || rule === "except");
    }
    togglePages();
    $(document).on("change", 'input[name="advanced_preloader_display[show_on]"]', togglePages);

    $(document).on("input", ".ap-page-filter", function () {
        const term = $(this).val().toLowerCase().trim();
        $(this).siblings(".ap-page-list").find("li").each(function () {
            $(this).toggle(!term || $(this).text().toLowerCase().indexOf(term) > -1);
        });
    });
    $(document).on("change", ".ap-page-list input", function () {
        const $picker = $(this).closest(".ap-page-picker");
        $picker.find(".ap-page-count").text($picker.find(".ap-page-list input:checked").length);
    });

    // Media uploader handler
    $(".upload_image_button").on("click", function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: "Select or Upload Preloader Image",
            button: { text: "Use this image" },
            multiple: false
        });

        mediaUploader.on("select", function () {
            const attachment = mediaUploader.state().get("selection").first().toJSON();
            $("#advanced_preloader_image").val(attachment.id);
            $("#advanced_preloader_image_url").val(attachment.url).trigger("change");
            $("#preloader_image_preview").html(
                `<img src="${attachment.url}" style="max-width: 200px; height: auto;" />`
            );
            $(".remove_image_button").show();
        });

        mediaUploader.open();
    });

    // Image removal handler
    $(".remove_image_button").on("click", function (e) {
        e.preventDefault();
        $("#advanced_preloader_image").val("");
        $("#advanced_preloader_image_url").val("").trigger("change");
        $("#preloader_image_preview").html("");
        $(this).hide();
    });

    // Initialize color pickers
    // Color pickers: refresh the live preview as the color changes
    $('.color-picker').wpColorPicker({
        change: function (event, ui) {
            $(event.target).val(ui.color.toString()).trigger("input");
        },
        clear: function () {
            $(this).closest(".wp-picker-container").find(".color-picker").trigger("input");
        }
    });
});
