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

	/* ---------- 1.1: focal point ---------- */

	function focalSave($box, x, y, reset) {
		var id = $box.data('id'), $msg = $box.closest('.ccpc-social-left').find('.ccpc-focal-msg');
		$msg.text(CCPC.i18n.working);
		return post('ccpc_set_focal', { id: id, x: x, y: y, reset: reset ? 1 : 0 }).done(function (r) {
			if (!r.success) { $msg.text(r.data || 'Error'); return; }
			var left = $box.closest('.ccpc-social-left');
			left.find('.ccpc-focal-x').val(r.data.x);
			left.find('.ccpc-focal-y').val(r.data.y);
			$box.find('.ccpc-focal-dot').css({ left: (r.data.x * 100) + '%', top: (r.data.y * 100) + '%' });
			left.find('.ccpc-focal-readout').text(r.data.set ? Math.round(r.data.x * 100) + '% / ' + Math.round(r.data.y * 100) + '%' : 'centre (default)');
			$msg.text(CCPC.i18n.done + ' Crops will be re-cut on next export.');
			$('.ccpc-crops-status .ccpc-dot').removeClass('is-on');
		}).fail(function () { $msg.text('Request failed'); });
	}

	$(document).on('click', '.ccpc-focal', function (e) {
		var $box = $(this), off = $box.offset();
		var x = (e.pageX - off.left) / $box.outerWidth(), y = (e.pageY - off.top) / $box.outerHeight();
		x = Math.min(1, Math.max(0, x)); y = Math.min(1, Math.max(0, y));
		$box.find('.ccpc-focal-dot').css({ left: (x * 100) + '%', top: (y * 100) + '%' });
		focalSave($box, x.toFixed(4), y.toFixed(4), false);
	});

	$(document).on('click', '.ccpc-focal-reset', function () {
		var $box = $(this).closest('.ccpc-social-left').find('.ccpc-focal');
		focalSave($box, 0.5, 0.5, true);
	});

	/* ---------- 1.1: social exports ---------- */

	function exportUrl($btn) {
		var $wrap = $btn.closest('.ccpc-social-right');
		var base = $btn.closest('.ccpc-export-buttons').data('base');
		var url = base + '&format=' + encodeURIComponent($btn.data('format'));
		url += '&type=' + encodeURIComponent($wrap.find('.ccpc-export-type').val() || 'jpg');
		url += '&wm=' + ($wrap.find('.ccpc-export-wm').is(':checked') ? '1' : '0');
		return url;
	}

	$(document).on('click', '.ccpc-export-btn', function (e) {
		e.preventDefault();
		window.location.href = exportUrl($(this));
	});

	$(document).on('click', '.ccpc-make-crops', function () {
		var $b = $(this), $msg = $b.siblings('.ccpc-crops-msg'), $wrap = $b.closest('.ccpc-social-right');
		$b.prop('disabled', true); $msg.text(CCPC.i18n.working);
		post('ccpc_make_crops', { id: $b.data('id'), watermark: $wrap.find('.ccpc-export-wm').is(':checked') ? 1 : 0, force: 0 }).done(function (r) {
			$b.prop('disabled', false);
			if (!r.success) { $msg.text(r.data || 'Error'); return; }
			var n = 0;
			$.each(r.data, function (slot, d) {
				n++;
				var $li = $wrap.find('.ccpc-crops-status li[data-format="' + d.format + '"]');
				if (!$li.length) { return; }
				$li.find('.ccpc-dot').addClass('is-on');
				$li.find('a, em').remove();
				$li.append(' ', $('<a target="_blank" rel="noopener">JPG</a>').attr('href', d.jpg));
				if (d.webp) { $li.append(' · ', $('<a target="_blank" rel="noopener">WebP</a>').attr('href', d.webp)); }
				if (d.watermark) { $li.append(' (credit)'); }
			});
			$msg.text(CCPC.i18n.done + ' ' + n + ' crops cached.');
		}).fail(function () { $b.prop('disabled', false); $msg.text('Request failed'); });
	});

	/* ---------- 1.1: resize one ---------- */

	$(document).on('click', '.ccpc-resize-btn', function () {
		var $b = $(this), $p = $b.closest('p'), edge = parseInt($p.find('.ccpc-resize-edge').val(), 10), $msg = $p.find('.ccpc-resize-msg');
		if (!edge || edge < 400) { $p.find('.ccpc-resize-edge').focus(); return; }
		if (!window.confirm(CCPC.i18n.confirmResize)) { return; }
		$b.prop('disabled', true); $msg.text(CCPC.i18n.working);
		post('ccpc_resize', { id: $b.data('id'), long_edge: edge }).done(function (r) {
			$b.prop('disabled', false);
			if (!r.success) { $msg.text(r.data || 'Error'); return; }
			$msg.text(CCPC.i18n.done + ' Now ' + r.data.width + '×' + r.data.height + ' (' + fmtBytes(r.data.bytes_before) + ' → ' + fmtBytes(r.data.bytes_after) + ', from ' + r.data.source + '). Reload to see it.');
		}).fail(function () { $b.prop('disabled', false); $msg.text('Request failed'); });
	});

	function fmtBytes(b) {
		b = parseInt(b, 10) || 0;
		if (b > 1048576) { return (b / 1048576).toFixed(1) + ' MB'; }
		if (b > 1024) { return Math.round(b / 1024) + ' KB'; }
		return b + ' B';
	}

	/* ---------- 1.1: WebP preset <-> quality ---------- */

	$('#ccpc-webp-preset').on('change', function () {
		var q = parseInt($(this).find('option:selected').data('q'), 10);
		if (q) { $('#ccpc-webp-quality').val(q); }
	});
	$('#ccpc-webp-quality').on('input', function () {
		$('#ccpc-webp-preset').val('custom');
	});

	/* ---------- 1.1: optimise all ---------- */

	var optRunning = false;

	function optRender(job) {
		var pct = job.total ? Math.round(100 * job.processed / job.total) : 0;
		$('#ccpc-opt-bar').css('width', pct + '%');
		$('#ccpc-opt-status').text(job.processed + ' / ' + job.total + ' processed · ' + fmtBytes(job.before) + ' → ' + fmtBytes(job.after)
			+ ' (' + (job.before ? Math.round(100 - 100 * job.after / Math.max(1, job.before)) : 0) + '% saved) · ' + job.resized + ' resized · ' + job.reencoded + ' JPEGs re-encoded' + (job.finished ? ' · finished' : ''));
		if (job.last && job.last.length) {
			var $log = $('#ccpc-opt-log');
			job.last.forEach(function (it) {
				$log.prepend($('<div class="ccpc-opt-row">').text('#' + it.id + ' ' + it.file + ': ' + (it.actions || []).join('; ') + ' — ' + fmtBytes(it.before) + ' → ' + fmtBytes(it.after)));
			});
			$log.children().slice(40).remove();
		}
	}

	function optStep(restart) {
		if (!optRunning) { return; }
		post('ccpc_optimise_batch', { restart: restart ? 1 : 0, reencode_jpeg: $('#ccpc-opt-reencode').is(':checked') ? 1 : 0 }).done(function (r) {
			if (!r.success) { $('#ccpc-opt-status').text(r.data || 'Error'); optStop(); return; }
			optRender(r.data);
			if (r.data.finished) { optStop(); $('#ccpc-opt-start').text('Start again').data('resume', 0); return; }
			optStep(false);
		}).fail(function () { $('#ccpc-opt-status').append(' — request failed; press Resume.'); optStop(); $('#ccpc-opt-start').text('Resume').data('resume', 1); });
	}

	function optStop() {
		optRunning = false;
		$('#ccpc-opt-start').prop('disabled', false);
		$('#ccpc-opt-pause').prop('disabled', true);
	}

	$('#ccpc-opt-start').on('click', function () {
		var $b = $(this), resume = parseInt($b.data('resume'), 10) === 1;
		optRunning = true;
		$b.prop('disabled', true);
		$('#ccpc-opt-pause').prop('disabled', false);
		if (!resume) { $('#ccpc-opt-log').empty(); }
		optStep(!resume);
	});

	$('#ccpc-opt-pause').on('click', function () {
		optStop();
		$('#ccpc-opt-start').text('Resume').data('resume', 1);
	});

	$('#ccpc-opt-reset').on('click', function (e) {
		e.preventDefault();
		optStop();
		post('ccpc_optimise_reset', {}).done(function () {
			$('#ccpc-opt-bar').css('width', '0%');
			$('#ccpc-opt-status').text('');
			$('#ccpc-opt-log').empty();
			$('#ccpc-opt-start').text('Start').data('resume', 0);
		});
	});

	$('#ccpc-opt-estimate').on('click', function () {
		var $b = $(this).prop('disabled', true), $o = $('#ccpc-opt-estimate-out').text(CCPC.i18n.working);
		var acc = { count: 0, bytes: 0, no_webp: 0, over_cap: 0 };
		(function step(offset) {
			post('ccpc_optimise_estimate', { offset: offset }).done(function (r) {
				if (!r.success) { $o.text(r.data || 'Error'); $b.prop('disabled', false); return; }
				if (r.data.count !== undefined) { acc.count = r.data.count; }
				acc.bytes += r.data.bytes; acc.no_webp += r.data.no_webp; acc.over_cap += r.data.over_cap;
				$o.text(acc.count + ' images · ' + fmtBytes(acc.bytes) + ' on disk · ' + acc.no_webp + ' without WebP · ' + acc.over_cap + ' over the long-edge cap' + (r.data.next !== null ? ' …' : '.'));
				if (r.data.next !== null) { step(r.data.next); } else { $b.prop('disabled', false); }
			}).fail(function () { $o.text('Request failed'); $b.prop('disabled', false); });
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
