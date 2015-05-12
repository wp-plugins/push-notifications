function showLengthOver() {
	var max_length = 25;
	var critical_color = 'red';
	var default_color = '#b1b6c3';
	var safari_title = document.getElementById('pushwoosh_safari_title');
	if (safari_title != undefined) {
		var title_length = safari_title.value.length;
		var result = max_length - title_length;
		if (result < 6) {
			document.getElementById('pushwoosh_length_over').style.color = critical_color;
		} else {
			document.getElementById('pushwoosh_length_over').style.color = default_color;
		}
		document.getElementById('pushwoosh_length_over').innerHTML = result;
	}
}

document.addEventListener('DOMContentLoaded',
	function () {
		showLengthOver();
	},
	false
);
