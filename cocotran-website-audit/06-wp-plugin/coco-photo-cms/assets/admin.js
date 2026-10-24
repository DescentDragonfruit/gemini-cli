/* global jQuery, CCPC */
(function ($) {
	'use strict';

	function post(action, data) {
		return $.post(CCPC.ajax, $.extend({ action: action, nonce: CCPC.nonce }, data));
	}

	// Rename (attachment edit screen + media modal).
	$(document).on('click', '.ccpc-rename-btn', function () {
		var $btn = $(this), id = $btn.data('id');
		var $wrap = $btn.closest('td, .setting, p');
		if ($wrap.find('.ccpc-rename-input').length) { return; }
		var $in = $('<input type="text" class="ccpc-rename-input" placeholder="new-file-name (no extension)">');
		var $ok = $('<button type="button" class="button button-small button-primary">OK</button>');
		var $msg = $('<span class="ccpc-msg"></span>');
		$btn.after($in, ' ', $ok, ' ', $msg);
		$ok.on('click', function () {
			var stem = $.trim($in.val());
			if (!stem) { return; }
			$msg.text(CCPC.i18n.working);
			post('ccpc_rename', { id: id, stem: stem }).done(function (r) {
				if (r.success) {
					$wrap.find('code').first().text(r.data.file);
					$msg.text(CCPC.i18n.done + ' ' + Object.keys(r.data.map || {}).length + ' URLs redirected.');
					$in.remove(); $ok.remove();
				} else {
					$msg.text(r.data || 'Error');
				}
			}).fail(function () { $msg.text('Request failed'); });
		});
	});

	// Replace only here (swap one usage).
	$(document).on('click', '.ccpc-swap-btn', function () {
		var $btn = $(this), $row = $btn.closest('tr');
		var newId = parseInt($row.find('.ccpc-swap-id').val(), 10);
		if (!newId) { $row.find('.ccpc-swap-id').focus(); return; }
		$btn.prop('disabled', true).text(CCPC.i18n.working);
		post('ccpc_swap_usage', {
			post_id: $btn.data('post'),
			location: $btn.data('location'),
			old_id: $btn.data('old'),
			new_id: newId
		}).done(function (r) {
			if (r.success) {
				$row.css('opacity', 0.5).find('td').last().text('Swapped to #' + newId);
			} else {
				$btn.prop('disabled', false).text('Swap');
				window.alert(r.data || 'Error');
			}
		});
	});

	// Media modal: warn on empty alt before the async save goes out.
	$(document).on('blur', '.attachment-details [data-setting="alt"] textarea, .attachment-details [data-setting="alt"] input', function () {
		var $f = $(this);
		$f.toggleClass('ccpc-alt-empty', !$.trim($f.val()));
	});

	// Bulk WebP.
	$('#ccpc-bulk-webp').on('click', function () {
		var $b = $(this).prop('disabled', true), $s = $('#ccpc-bulk-status'), total = 0, made = 0;
		(function step(offset) {
			post('ccpc_bulk_webp', { offset: offset }).done(function (r) {
				if (!r.success) { $s.text(r.data || 'Error'); $b.prop('disabled', false); return; }
				total += r.data.processed; made += r.data.made;
				$s.text('Checked ' + total + ', generated ' + made + '…');
				if (r.data.next !== null) { step(r.data.next); } else { $s.text('Done. Checked ' + total + ', generated WebP for ' + made + '.'); $b.prop('disabled', false); }
			}).fail(function () { $s.text('Request failed at offset ' + offset); $b.prop('disabled', false); });
		})(0);
	});

	// Missing alt list.
	$('#ccpc-missing-alt').on('click', function () {
		var $list = $('#ccpc-missing-alt-list').text(CCPC.i18n.working);
		post('ccpc_missing_alt', {}).done(function (r) {
			if (!r.success) { $list.text(r.data || 'Error'); return; }
			if (!r.data.length) { $list.text('None. Every image has alt text.'); return; }
			var $t = $('<table class="widefat striped"><thead><tr><th>ID</th><th>Title</th><th>Used in</th><th></th></tr></thead><tbody></tbody></table>');
			r.data.forEach(function (row) {
				$('<tr>').append(
					$('<td>').text(row.id),
					$('<td>').text(row.title),
					$('<td>').text(row.usages + ' place(s)'),
					$('<td>').append($('<a class="button button-small">Edit</a>').attr('href', row.edit))
				).appendTo($t.find('tbody'));
			});
			$list.empty().append($('<p>').text(r.data.length + ' image(s) without alt text (showing up to 500).'), $t);
		});
	});
})(jQuery);
