;(function(window) {

	if (BX.Scale.Communicator) return;

	BX.Scale.Communicator = {
		url: null,
		sendRequest: function (params, callbacks, context, showWaiter)
		{
			if(!params || !this.url)
				return false;

			if(showWaiter)
				ShowWaitWindow();

			var postData = {
					params: params,
					sessid: BX.bitrix_sessid()
				};

			BX.ajax({
				timeout:   30,
				method:   'POST',
				dataType: 'json',
				url:       this.url,
				data:      postData,

				onsuccess: function(result)
				{
					CloseWaitWindow();
					if(callbacks && callbacks.onsuccess && typeof callbacks.onsuccess == "function")
						callbacks.onsuccess.call(context, result);
				},

				onfailure: function()
				{
					CloseWaitWindow();
					if(callbacks && callbacks.onfailure && typeof callbacks.onfailure == "function")
						callbacks.onfailure.call(context);
				}
			});
		}
	}
})(window);
