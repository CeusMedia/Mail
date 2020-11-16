    
jQuery(document).ready(function(){
    jQuery(".mail-list-preview-trigger").on("click", function(){
        var that = jQuery(this);
        var itemId = that.data("id");
        var hasHtml = that.data("html");
        var hasText = that.data("text");
        var action = hasHtml ? 'viewHtmlContent' : 'viewTextContent';
        var url = './?mailId=' + itemId + '&action=' + action;
        console.log({url: url});
        jQuery("#mail-ui-preview-iframe").attr("src", url);
    });
});