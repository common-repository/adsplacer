jQuery(document).ready(function($) {

	$('#adsplacer_settings_tabs').tabs();
	$('.custom-ads-tabs').tabs();

	$('.adsplacer_code').click(function() {
		var e=this;
		if (window.getSelection) {
			var s = window.getSelection();
			if (s.setBaseAndExtent) {
				s.setBaseAndExtent(e,0,e,e.innerText.length-1);
			} else {
				var r = document.createRange();
				r.selectNodeContents(e);
				s.removeAllRanges();
				s.addRange(r);
			}
		} else if (document.getSelection) {
			var s = document.getSelection();
			var r = document.createRange();
			r.selectNodeContents(e);
			s.removeAllRanges();
			s.addRange(r);
		} else if (document.selection) {
			var r = document.body.createTextRange();
			r.moveToElementText(e);
			r.select();
		}
	});




	$('#adplacer_exclude_all').on('change', function() {
		var checkboxes = $('.adsplacer_checkbox');
		var checkbox = $(this);
		var checkbox_other = checkboxes.not('#adplacer_exclude_all');

		if (checkbox.is(':checked')) {
			checkbox_other.attr('disabled', true);
		} else {
			checkbox_other.removeAttr('disabled');
		}
	});
});