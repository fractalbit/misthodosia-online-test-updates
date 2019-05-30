
$(function () {

	$('#gen-pdf').click(function (event) {
		event.preventDefault();
		$('#generating').fadeIn(400);
		$('#pdf-complete').hide();

		if ($(this).hasClass('confirm')) {
			var message = $(this).attr('rel');
			if (!confirm(message)) return;
		}

		var periodId = $('#pdf-period').val();
		var periodTxt = $('#pdf-period option:selected').text();
		// console.log(periodId);

		$.ajax({
			url: "generate-pdf.php",
			dataType: "html",
			data: { pid: periodId },
			success: function (msg) {
				$('#generating').hide();
				$('#pdf-complete').text(periodTxt);
				$('#pdf-complete').fadeIn(400);
				if (msg != '') alert(msg);
			}
		});

	});

	$('a.scrollLink').slideto({
		speed: 'slow'
	});

});

