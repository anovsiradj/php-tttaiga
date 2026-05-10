
function taigaViewAdjustTable(el) {
	$(el).find('table').each(function () {
		$(this).addClass('table table-sm table-bordered')
	})
}

function taigaViewAdjust(el) {
	taigaViewAdjustTable(el)
}
