var AdminMenu = {
	init: function() {
		// Show and activate the admin menu using jQuery UI functions and styles
		$('#biscuit-admin-menu').show();
		$('#biscuit-admin-menu-top-level').accordion({autoHeight: false});
		$('#biscuit-admin-menu-top-level').click(function(event) {
			// When clicking anywhere within the admin menu, prevent propagation of click events so the body click event will not cause the
			// menu to close while allowing the default events of items within the menu to occur.
			event.stopPropagation();
		})
		$('#biscuit-admin-menu-activator').button({
			icons: {
				primary: 'ui-icon-wrench'
			},
			text: false
		}).click(function() {
			$('body').unbind('click',AdminMenu.hide);
			var is_showing = $('#biscuit-admin-menu-top-level').css('display') == 'block';
			if (is_showing) {
				AdminMenu.hide();
			} else {
				AdminMenu.show();
			}
		});
		$('#biscuit-admin-menu-top-level').hide();
	},
	show: function() {
		$('#biscuit-admin-menu-top-level').show('slide',{},'fast',function() {
			$('body').bind('click',AdminMenu.hide);
		});
	},
	hide: function() {
		$('#biscuit-admin-menu-top-level').hide('slide',{},'fast',function() {
			$('body').unbind('click',AdminMenu.hide);
		});
	}
}

$(document).ready(function() {
	AdminMenu.init();
});
