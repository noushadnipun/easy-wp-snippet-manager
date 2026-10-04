/* Easy WP Snippets Manager - admin editor behaviour. */
jQuery(function ($) {
	var cfg = window.ewpsmConfig || { labels: {}, detected: '%s', settings: null };
	var modes = { html: 'text/html', css: 'text/css', js: 'text/javascript', php: 'application/x-httpd-php' };
	var $ta = $('#ewpsm_code'), editor = null, timer = null;

	if (cfg.settings && window.wp && wp.codeEditor) {
		editor = wp.codeEditor.initialize($ta, cfg.settings);
	}

	function getCode() { return editor ? editor.codemirror.getValue() : $ta.val(); }

	function detect(c) {
		c = $.trim(c);
		if (!c) { return ''; }
		if (/^<\?(php)?\s/i.test(c)) { return 'php'; }
		if (/<\s*\/?\s*[a-z][a-z0-9-]*(\s[^>]*)?>/i.test(c) || c.indexOf('<!--') !== -1) { return 'html'; }
		if (/(?:\b(?:add|remove|do|apply)_(?:action|filter)\s*\(|\badd_shortcode\s*\(|\bregister_[a-z_]+\s*\(|\bwp_[a-z_]+\s*\(|\b(?:get|update|delete)_option\s*\(|\bis_admin\s*\(|\bdefine\s*\(|\$this->|\bnamespace\s+\w|^\s*\$[a-zA-Z_]\w*\s*(?:=|\.=)|^\s*echo\s|^\s*(?:if|foreach)\s*\(.*\$\w+)/m.test(c)) { return 'php'; }
		if (/\b(?:function|var|let|const)\b|\bdocument\.|\bwindow\.|\bconsole\.|addEventListener|\bjQuery\b|\$\s*\(|=>/.test(c)) { return 'js'; }
		if (/[^{}]+\{\s*[a-z-]+\s*:[^{}]*\}/i.test(c) || /^\s*@(media|import|font-face|keyframes)/i.test(c)) { return 'css'; }
		return 'html';
	}

	function refresh() {
		var t = $('#ewpsm_override').val() || detect(getCode());
		var $b = $('#ewpsm_badge');
		if (t && cfg.labels[t]) {
			$b.text(cfg.detected.replace('%s', cfg.labels[t])).show();
		} else {
			$b.hide();
		}
		$('#ewpsm_place_wrap').toggle(t !== 'php');
		if (editor) { editor.codemirror.setOption('mode', modes[t] || 'text/html'); }
	}

	function later() { clearTimeout(timer); timer = setTimeout(refresh, 250); }

	$('#ewpsm_override').on('change', refresh);
	if (editor) { editor.codemirror.on('change', later); } else { $ta.on('input', later); }
	$('#post').on('submit', function () { if (editor) { editor.codemirror.save(); } });
	refresh();
});
