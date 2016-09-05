(function($) {
    $(document).ready(function() {
		$('.ajaxController').each(function() {
			var intervalSecs = $(this).data('ajax-interval') || 30;

			console.log('Registering AJAX-Handler for ', $(this));
			console.log('Data', $(this).data('ajax'));

			$(this).on('ajax-update', function() {
				var dataString = $(this).data('ajax'),
					dataRequest = null, dataObj = {},
					pageId = null
				;

				try
				{
					pageId = $('body').attr('id').match(/pageUID-([0-9]+)/).pop();
				}
				catch(e)
				{}

				// Sanitize Data-String to JS-Object
/*
				dataString = dataString.replace(/([\{\}\:\',]+[ ]*)+/g, function(s) {
					s = s.replace(/[']/g,'"');

					switch(s) {
						case ',':
						case ':':
						case ':{': s = '"'+s+'"'; break;

						case '{':	s = s+'"'; break;

						case '}':	s = '"' + s;
					}

					return s;
				});
*/

				// Add missing quotes for object-properties
				dataString = dataString.replace(/[']/g,'"').replace(/([a-zA-Z0-9]+)([\:])/g, '"$1"$2');

				//console.log(dataString);
				dataObj = $.parseJSON(dataString);

/*
				dataRequest = [];
				dataRequest['eID'] = 'romantica_' + dataObj['context'];
				dataRequest['controller'] = dataObj['controller'];
				dataRequest['action'] = dataObj['action'];
				delete dataObj['controller'], dataObj['action'];

				console.log(dataRequest);
				if(dataRequest === null || dataRequest === '') return;
*/

				if(dataObj['context'] === null || dataObj['context'] === 'null') return;
				if(dataObj['controller'] === null || dataObj['controller'] === 'null') return;
				if(dataObj['action'] === null || dataObj['action'] === 'null') return;

				dataObj['eID'] = 'romantica_' + dataObj['context'];
				delete dataObj['context'];

//				console.log('AJAX', dataString, dataObj);

				$.ajax({
					url: '/',
					data: dataObj,
					context: this
				})
				.success(function(data, textStatus, jqXHR) {
					if(!data || jqXHR.status !== 200) return;
					$(this).html(data);
				});
			})
			.trigger('ajax-update');

			var $context = this;

			setInterval(function() {
				$($context).trigger('ajax-update');
			}, intervalSecs * 1000);
		});
    });
}) (jQuery);
