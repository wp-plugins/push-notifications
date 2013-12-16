function showLengthOver() {
	max_length = 25;
	critical_color = 'red';
	default_color = '#b1b6c3';
	title_length = document.getElementById('pushwoosh_safari_title').value.length;
	result = max_length - title_length;
	if (result < 6) {
		document.getElementById('pushwoosh_length_over').style.color = critical_color;
	} else {
		document.getElementById('pushwoosh_length_over').style.color = default_color;
	}
	document.getElementById('pushwoosh_length_over').innerHTML = result;
}

document.addEventListener('DOMContentLoaded',
	function () {
		showLengthOver();
	},
	false
);