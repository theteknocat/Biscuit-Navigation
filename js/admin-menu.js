var AdminMenu = {
	auto_close_timer: null,
	init: function() {
		// Show and activate the admin menu using jQuery UI functions and styles
		$('#biscuit-admin-menu').show();
		$('#biscuit-admin-menu-top-level').accordion({
			active: false,
			icons: {
				'header': 'ui-icon-carat-1-e',
				'headerSelected': 'ui-icon-carat-1-s'
			},
			collapsible: true,
			autoHeight: false
		});
		$('#biscuit-admin-menu-top-level').click(function(event) {
			// When clicking anywhere within the admin menu, prevent propagation of click events so the body click event will not cause the
			// menu to close while allowing the default events of items within the menu to occur.
			event.stopPropagation();
			AdminMenu.set_auto_hide();
		});
		$('#biscuit-admin-menu-top-level').mouseover(function() {
			AdminMenu.set_auto_hide();
		});
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
	set_auto_hide: function() {
		clearTimeout(AdminMenu.auto_close_timer);
		AdminMenu.auto_close_timer = setTimeout(AdminMenu.hide, 10000);
	},
	show: function() {
		$('#biscuit-admin-menu-top-level').show('slide',{},'fast',function() {
			AdminMenu.set_auto_hide();
			$('body').bind('click',AdminMenu.hide);
		});
	},
	hide: function() {
		clearTimeout(AdminMenu.auto_close_timer);
		$('#biscuit-admin-menu-top-level').hide('slide',{},'fast',function() {
			$('body').unbind('click',AdminMenu.hide);
		});
	}
}

$(document).ready(function() {
	AdminMenu.init();
});
