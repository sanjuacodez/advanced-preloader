jQuery(document).ready(function ($) {
    // Initialize media uploader variable
    let mediaUploader;

    // Toggle fields based on preloader type
    function toggleFields() {
        const type = $("#preloader_type").val();
        const isText = type === "text" || type === "both";
        
        // Toggle text-related fields
        $("#advanced_preloader_text").closest("tr").toggle(isText);
        $("#text_display_mode").closest("tr").toggle(isText);
        
        // Toggle image-related fields
        $("#advanced_preloader_image").closest("tr").toggle(type !== "text");
        $(".upload_image_button, .remove_image_button").closest("td").toggle(type !== "text");
        
        // Toggle text-related fields
        $("#advanced_preloader_text").closest("tr").toggle(type !== "image");
        
        // Toggle layout order
        $("#layout_order_wrapper").closest("tr").toggle(type === "both");
    }

    // Initial setup
    toggleFields();
    
    // Event listeners
    $("#preloader_type").on('change', toggleFields);

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
            $("#advanced_preloader_image").val(attachment.url);
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
        $("#preloader_image_preview").html("");
        $(this).hide();
    });

    // Initialize color pickers
    $('.color-picker').wpColorPicker();
});