"undefined"==typeof jQuery&&console.error("jQuery is not loaded. LinkCentral may not work correctly."),function(n){"use strict";function e(e){var t=n("<input>");n("body").append(t),t.val(e).select(),document.execCommand("copy"),t.remove()}function t(n,e,t){var a=t||n.text();n.text(e),setTimeout((function(){n.text(a)}),2e3)}n(document).ready((function(){function a(e){var t=n("#post_ID").val()||0;n.ajax({url:linkcentral_admin.ajax_url,type:"POST",data:{action:"linkcentral_check_slug",nonce:linkcentral_admin.nonce,slug:e,post_id:t},success:function(e){e.success?n("#post_name").val(e.data.unique_slug):alert(e.data.message)},error:function(){alert("Error checking slug.")}})}n("#post").on("submit",(function(e){var t,a=n("#linkcentral_destination_url").val(),l=n("#post_name").val();return a&&l?(t=a,new RegExp("^(https?:\\/\\/)?((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|((\\d{1,3}\\.){3}\\d{1,3}))(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*(\\?[;&a-z\\d%_.~+=-]*)?(\\#[-a-z\\d_]*)?$","i").test(t)?void 0:(e.preventDefault(),alert(linkcentral_admin.invalid_url_message),!1)):(e.preventDefault(),alert(linkcentral_admin.required_fields_message),!1)})),n("#title").on("blur",(function(){var e=n(this).val();e&&!n("#post_name").val()&&a(e.toLowerCase().replace(/[^a-z0-9]+/g,"-").replace(/^-+|-+$/g,""))})),n("#post_name").on("blur change",(function(){var e=n(this).val();e&&a(e)})),n(".linkcentral-copy-url").on("click",(function(a){a.preventDefault(),e(n(this).data("url")),t(n(this),linkcentral_admin.copied_message,linkcentral_admin.copy_message)})),"undefined"!=typeof linkcentral_post_type&&"linkcentral_link"==linkcentral_post_type&&n('#post-status-select option[value="pending"]').remove(),n("#linkcentral-copy-url").on("click",(function(){e(n("#linkcentral-url-prefix").text().trim()+n("#post_name").val()),t(n(this),linkcentral_admin.copied_message)})),n(".linkcentral-copy-shortcode").on("click",(function(a){a.preventDefault(),e(n(this).data("shortcode")),t(n(this),linkcentral_admin.copied_message,linkcentral_admin.copy_shortcode_message)})),linkcentral_admin.can_use_premium_code__premium_only&&n("#linkcentral_css_classes_option").on("change",(function(){"default"===n(this).val()?n("#linkcentral_custom_css_classes").hide():n("#linkcentral_custom_css_classes").show()})),n(".linkcentral-edit-note").on("click",(function(e){e.preventDefault(),n(".linkcentral-note-display").hide(),n(".linkcentral-note-edit").show()})),n(".linkcentral-cancel-edit").on("click",(function(){n(".linkcentral-note-edit").hide(),n(".linkcentral-note-display").show()})),n(".linkcentral-save-note").on("click",(function(){var e=n("#linkcentral_note").val();n(".linkcentral-note-text").text(e),n(".linkcentral-note-edit").hide(),n(".linkcentral-note-display").show()}))}))}(jQuery);