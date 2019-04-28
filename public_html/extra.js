'use strict';

// Handle 'navigate-back' links
$(function() {
	$('a.navigate-back').on('click', function(e) {
		window.history.back();
		event.stopPropagation();
	});
});

// Remember the last-selected tab in a tab group
$(function() {
	if(sessionStorage) {
		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			//save the latest tab
			sessionStorage.setItem('lastTab' + location.pathname, $(e.target).attr('href'));
		});

		//go to the latest tab, if it exists:
		var lastTab = sessionStorage.getItem('lastTab' + location.pathname);

		if (lastTab) {
			$('a[href="' + lastTab + '"]').tab('show');
		} else {
			$('a[data-toggle="tab"]:first').tab('show');
		}
	}

	get_tab_from_location();
	window.onpopstate = function(event) {
		get_tab_from_location();
	}
	function get_tab_from_location() {
		// Javascript to enable link to tab
		var url = document.location.toString();
		if(url.match('#')) {
			$('.nav-tabs a[href="#'+url.split('#')[1]+'"]').tab('show');
		}
	}

	// Do the location modifying code after all other setup, since we don't want the initial loading to trigger this
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		if(history) {
			history.replaceState(null, null, e.target.href);
		} else {
			window.location.hash = e.target.hash;
		}
	});
});

// Remember the expanded-state of a collapsible section
$(function() {
	// Do the location modifying code after all other setup, since we don't want the initial loading to trigger this
	$('.panel-collapse').on('show.bs.collapse', function (e) {
		if(history) {
			history.replaceState(null, null, '#' + e.target.id);
		} else {
			window.location.hash = e.target.id;
		}
	});

});

// Show only chosen fingerprint hash format in list views
$(function() {
	$('table th.fingerprint').first().each(function() {
		$(this).append(' ');
		var select = $('<select>');
		var options = ['MD5', 'SHA256'];
		for(var i = 0, option; option = options[i]; i++) {
			select.append($('<option>').text(option).val(option));
		}
		if(localStorage) {
			var fingerprint_hash = localStorage.getItem('preferred_fingerprint_hash');
			if(fingerprint_hash) {
				select.val(fingerprint_hash);
			}
		}
		$(this).append(select);
		select.on('change', function() {
			if(this.value == 'SHA256') {
				$('span.fingerprint_md5').hide();
				$('span.fingerprint_sha256').show();
			} else {
				$('span.fingerprint_sha256').hide();
				$('span.fingerprint_md5').show();
			}
			if(localStorage) {
				localStorage.setItem('preferred_fingerprint_hash', this.value);
			}
		});
	});
});

// Add confirmation dialog to all submit buttons with data-confirm attribute
$(function() {
	$('button[type="submit"][data-confirm]').each(function() {
		$(this).on('click', function() { return confirm($(this).data('confirm')); });
	});
});

// Add "clear field" button functionality
$(function() {
	$('button[data-clear]').each(function() {
		$(this).on('click', function() { this.form[$(this).data('clear')].value = ''; });
	});
});

// Server sync status
$(function() {
	var status_div = $('#server_sync_status');
	status_div.each(function() {
		if(status_div.data('class')) {
			update_server_sync_status(status_div.data('class'), status_div.data('message'));
		} else {
			$('span', status_div).addClass('text-warning');
			$('span', status_div).text('Pending');
			$('span.server_account_sync_status').addClass('text-warning');
			$('span.server_account_sync_status').text('Pending');
			var timeout = 1000;
			var max_timeout = 10000;
			get_server_sync_status();
		}
		function get_server_sync_status() {
			var xhr = $.ajax({
				url: window.location.pathname + '/sync_status',
				dataType: 'json'
			});
			xhr.done(function(status) {
				if(status.pending) {
					timeout = Math.min(timeout * 1.5, max_timeout);
					setTimeout(get_server_sync_status, timeout);
				} else {
					var classname;
					if(status.sync_status == 'sync success') classname = 'success';
					if(status.sync_status == 'sync failure') classname = 'danger';
					if(status.sync_status == 'sync warning') classname = 'warning';
					update_server_sync_status(classname, status.last_sync.details);
				}
			});
		}
		function update_server_sync_status(classname, message) {
			$('span', status_div).removeClass('text-success text-warning text-danger');
			$('span', status_div).addClass('text-' + classname);
			$('span', status_div).text(message);
			if(classname == 'success') {
				$('a', status_div).addClass('hidden');
			} else {
				$('a', status_div).removeClass('hidden');
				if(classname == 'warning') $('a', status_div).prop('href', '/help#sync_warning');
				if(classname == 'danger') $('a', status_div).prop('href', '/help#sync_error');
			}
			$('div.spinner', status_div).remove();
			$('button[name=sync]', status_div).removeClass('invisible');
		}
	});
});

// Profile add form - multiple server,service autocomplete
$(function() {
	var profile_server = $('input#profile_server');
	profile_server.each(function() {
		profile_server.on('keydown', function(event) {
			var keycode = (event.keyCode ? event.keyCode : event.which);
			if((keycode == 13 || keycode == 32 || keycode == 188) && $("#profile_server").val() != '') { // Enter, space, comma
				appendServer();
				// Reset focus to remove <datalist> autocomplete dialog
				$("#profile_server").blur();
				$("#profile_server").focus();
				return false;
			}
		});
		profile_server.on('blur', function(event) {
			if($("#profile_server").val()) {
				appendServer();
			}
		});
		function appendServer() {
			if($("#profile_servers").val()) {
				$("#profile_servers").val($("#profile_servers").val() + ', ' + $("#profile_server").val());
			} else {
				$("#profile_servers").val($("#profile_server").val());
			}
			$("#profile_server").val("");
			$("#profile_servers").removeClass('hidden');
		}
		$('input#profile_servers').on('blur', function(event) {
			if(!$("#profile_servers").val()) {
				$("#profile_servers").addClass('hidden');
			}
		});
		if($("#profile_servers").val()) {
			$("#profile_servers").removeClass('hidden');
		}
	});

	var profile_service = $('input#profile_service');
	profile_service.each(function() {
		profile_service.on('keydown', function(event) {
			var keycode = (event.keyCode ? event.keyCode : event.which);
			if((keycode == 13 || keycode == 32 || keycode == 188) && $("#profile_service").val() != '') { // Enter, space, comma
				appendService();
				// Reset focus to remove <datalist> autocomplete dialog
				$("#profile_service").blur();
				$("#profile_service").focus();
				return false;
			}
		});
		profile_service.on('blur', function(event) {
			if($("#profile_service").val()) {
				appendService();
			}
		});
		function appendService() {
			if($("#profile_services").val()) {
				$("#profile_services").val($("#profile_services").val() + ', ' + $("#profile_service").val());
			} else {
				$("#profile_services").val($("#profile_service").val());
			}
			$("#profile_service").val("");
			$("#profile_services").removeClass('hidden');
		}
		$('input#profile_services').on('blur', function(event) {
			if(!$("#profile_services").val()) {
				$("#profile_services").addClass('hidden');
			}
		});
		if($("#profile_services").val()) {
			$("#profile_services").removeClass('hidden');
		}
	});
});
