/**
 * CS Filter — أجاكس فلترة الكورسات (Vanilla JS، من غير jQuery).
 * بيسمع لتغيّر أي فلتر + السيرش (debounce)، ويحدّث الجريد والعدّاد.
 * فيه console.log للتصحيح: افتح F12 => Console وشوف كل خطوة.
 */
(function () {
	'use strict';

	function log() {
		if (window.console) {
			console.log.apply(console, ['[CS-Filter]'].concat([].slice.call(arguments)));
		}
	}

	var form = document.getElementById('cs-filter-form');
	if (!form) { log('مفيش #cs-filter-form في الصفحة دي.'); return; }

	if (typeof CS_FILTER === 'undefined') {
		log('CS_FILTER مش موجود — الملف اتحمّل بس الـ wp_localize_script مش شغّال. الفلتر مش هيشتغل.');
		return;
	}

	var grid    = document.getElementById('cs-grid');
	var countEl = document.getElementById('cs-count');
	var termId  = form.getAttribute('data-term-id') || 0;
	var timer   = null;

	log('اتحمّل صح. ajax_url =', CS_FILTER.ajax_url, '| term_id =', termId);

	function val(name) {
		var el = form.querySelector('[name="' + name + '"]');
		return el ? el.value : '';
	}

	function run() {
		var params = new URLSearchParams({
			action:  'cs_courses_filter',
			term_id: termId,
			course:  val('course') || 0,
			q:       val('q') || '',
			mode:    val('mode') || '',
			lang:    val('lang') || '',
			loc:     val('loc') || '',
			month:   val('month') || 0
		});

		log('بيبعت request:', CS_FILTER.ajax_url + '?' + params.toString());

		if (grid) { grid.classList.add('cs-loading'); }

		fetch(CS_FILTER.ajax_url + '?' + params.toString(), { credentials: 'same-origin' })
			.then(function (r) { log('رد HTTP:', r.status); return r.json(); })
			.then(function (res) {
				log('الرد:', res);
				if (res && res.success) {
					if (grid)    { grid.innerHTML = res.data.html; }
					if (countEl) { countEl.textContent = res.data.count; }
				} else {
					log('success=false، مفيش تحديث.');
				}
			})
			.catch(function (err) { log('ERROR:', err); })
			.then(function () {
				if (grid) { grid.classList.remove('cs-loading'); }
			});
	}

	// تغيّر أي select => فلترة فورية.
	form.addEventListener('change', function (e) {
		if (e.target && e.target.tagName === 'SELECT') {
			log('اتغيّر select:', e.target.name, '=', e.target.value);
			run();
		}
	});

	// السيرش => debounce.
	form.addEventListener('input', function (e) {
		if (e.target && e.target.name === 'q') {
			clearTimeout(timer);
			timer = setTimeout(run, 350);
		}
	});

	// منع ريلود عند Enter/الزرار.
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		log('Search اتدوس');
		run();
	});
})();
