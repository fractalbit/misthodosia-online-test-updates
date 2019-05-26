
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

	$('.confirm_cleanup').click(function (event) {
		var message = $(this).attr('rel');
		if (!confirm(message)) {
			event.preventDefault();
			return;
		}
	});

	// Let's handle some AJAX requests, shall we?	
	$('.delete').click(function (event) {
		event.preventDefault();
		var params = $(this).attr('href');
		//alert(params);
		if ($(this).hasClass('confirm')) {
			var message = $(this).attr('rel');
			if (!confirm(message)) return;
		}

		$.ajax({
			url: "ajax-delete-file.php",
			data: params,
			dataType: "html",
			success: function (msg) {
				if (msg != '') alert(msg);
				location.reload();
			}
		});

	});

	$('#search_filter').focus();

	$('#search_filter').fastLiveFilter('#user-list');

	$('.toggle_xml_errors').click(function (event) {
		event.preventDefault();
		$('.xml-errors').toggle(300);
	});

	$('#show-more-log').click(function (event) {
		event.preventDefault();
		$('#all-update-log').toggle(300);
	});

	$('#accept-danger').click(function () {
		var accept = $(this).is(":checked");
		// console.log(accept);
		if (accept) {
			$('#start-update').prop('disabled', false);
		} else {
			$('#start-update').prop('disabled', true);
		}
	});

	$('#start-update').click(function () {
		$(this).prop('disabled', true);
	});

	var ajaxExecute = function (url) {
		return $.ajax({
			url: url,
			// dataType: "html",
		});
	};

	var beautifyError = function (jqXHR) {
		var errorText = '. <div class="error"><strong>Συνέβη ένα σφάλμα</strong>, ' + jqXHR.status + ' - ' + jqXHR.statusText + ': ' + jqXHR.responseText + '</div>';
		return errorText;
	};


	$('#start-update').click(function () {

		var alreadyFailed = false;

		$('#update-results').append('Παρακαλούμε περιμένετε όσο κατεβάζουμε την τελευταία έκδοση<span id="downloading" class="ajax-loader" style="display: inline-block;"><img src="img/loader-new.gif" style="position: relative; top: 6px; margin-right: 20px;" /></span>');

		var start_time = new Date().getTime();

		var getLatest = ajaxExecute('ajax-get-latest.php');

		getLatest.done(function (data) {
			var request_time = new Date().getTime() - start_time; // Second call execution time
			var info = '. <strong>' + data + '</strong> σε ' + (request_time / 1000).toFixed(1) + 's';
			$('#downloading').hide();
			$('#update-results').append(info);
		});

		getLatest.fail(function (jqXHR, textStatus, errorThrown) {
			var errorInfo = beautifyError(jqXHR);
			$('#downloading').hide();
			$('#update-results').append(errorInfo);
			alreadyFailed = true;
		});

		var extract = getLatest.then(function (data) {
			$('#update-results').append('<br><br>Παρακαλούμε περιμένετε όσο αποσυμπιέζονται τα απαραίτητα αρχεία<span id="extracting" class="ajax-loader" style="display: inline-block;"><img src="img/loader-new.gif" style="position: relative; top: 6px; margin-right: 20px;" /></span>');
			start_time = new Date().getTime(); // Reset the timer just befor the second execution
			return ajaxExecute('ajax-extract-release.php');
		});

		extract.done(function (data) {
			var request_time = new Date().getTime() - start_time; // Second call execution time
			var info = '. <strong>' + data + '</strong> σε ' + (request_time / 1000).toFixed(1) + 's';
			$('#extracting').hide();
			$('#update-results').append(info);
		});

		extract.fail(function (jqXHR, textStatus, errorThrown) {
			if (!alreadyFailed) {
				var errorInfo = beautifyError(jqXHR);
				$('#extracting').hide();
				$('#update-results').append(errorInfo);
			}
			alreadyFailed = true;
		});

		var copy = extract.then(function (data) {
			$('#update-results').append('<br><br>Παρακαλούμε περιμένετε όσο αντιγράφονται τα απαραίτητα αρχεία<span id="copying" class="ajax-loader" style="display: inline-block;"><img src="img/loader-new.gif" style="position: relative; top: 6px; margin-right: 20px;" /></span>');
			start_time = new Date().getTime(); // Reset the timer just befor the second execution
			return ajaxExecute('ajax-copy-release.php');
		});

		copy.done(function (data) {
			var request_time = new Date().getTime() - start_time; // Second call execution time
			var info = '. <strong>' + data + '</strong> σε ' + (request_time / 1000).toFixed(1) + 's';
			$('#copying').hide();
			$('#update-results').append(info);
		});

		copy.fail(function (jqXHR, textStatus, errorThrown) {
			if (!alreadyFailed) {
				var errorInfo = beautifyError(jqXHR);
				$('#copying').hide();
				$('#update-results').append(errorInfo);
			}
			alreadyFailed = true;
		});

		var cleanup = copy.then(function (data) {
			$('#update-results').append('<br><br>Εκκαθάριση προσωρινών αρχείων<span id="cleanup" class="ajax-loader" style="display: inline-block;"><img src="img/loader-new.gif" style="position: relative; top: 6px; margin-right: 20px;" /></span>');
			start_time = new Date().getTime(); // Reset the timer just befor the second execution
			return ajaxExecute('ajax-update-cleanup.php');
		});

		cleanup.done(function (data) {
			var request_time = new Date().getTime() - start_time; // Second call execution time
			var info = '. <strong>' + data + '</strong> σε ' + (request_time / 1000).toFixed(1) + 's';
			$('#cleanup').hide();
			$('#update-results').append(info);

			var finished = '<br><br>Η διαδικασία της αναβάθμισης ολοκληρώθηκε. Παρακαλούμε περιμένετε να γίνει επαναφόρτωση..';
			$('#update-results').append(finished);
			setTimeout(() => {
				window.location.replace("update.php");
			}, 3000);
		});

		cleanup.fail(function (jqXHR, textStatus, errorThrown) {
			if (!alreadyFailed) {
				var errorInfo = beautifyError(jqXHR);
				$('#cleanup').hide();
				$('#update-results').append(errorInfo);
			}
		});

	});


});

