/**
 * Class BX.Scale.Action
 * Describes actionr's props, view & behavior
 */
;(function(window) {

	if (BX.Scale.Action) return;

	/**
	 * Class BX.Scale.Action
	 * @constructor
	 */
	BX.Scale.Action = function (id, params)
	{
		this.id = id;
		this.name = params.NAME;
		this.userParams = params.USER_PARAMS;
		this.freeParams = {};

		if(params && params.TYPE !== undefined)
			this.type = params.TYPE;
		else
			this.type = "ACTION";

		if(this.type == "CHAIN" && params.ACTIONS !== undefined)
			this.actions = params.ACTIONS;

		this.currentOperation = "";
		this.paramsDialog = null;
		this.async = params.ASYNC == "Y";
		this.pageRefresh = params.PAGE_REFRESH == "Y";
		this.backupAlert = params.BACKUP_ALERT == "Y";
	};

	/**
	 * Returns list of  params to ask user
	 * @returns {{}}
	 */
	BX.Scale.Action.prototype.getUserParams = function()
	{
		var result = {};

		if(this.type == "CHAIN")
			result = this.extractUserParamsFromActions();
		else
			result = this.userParams;

		return result;
	};

	BX.Scale.Action.prototype.extractUserParamsFromActions = function()
	{
		var result = {};

		for(var actionId in this.actions)
		{
			var actUserParams = BX.Scale.actionsCollection.getObject(this.actions[actionId]).getUserParams();

			for(var paramId in actUserParams)
				result[paramId] = actUserParams[paramId];
		}

		return result;
	};

	/**
	 * Starts execution of the action
	 */
	BX.Scale.Action.prototype.start = function(serverHostname, paramsValues, skipBackupAlert)
	{
		if(this.backupAlert && !skipBackupAlert)
		{
			var _this = this;
			BX.Scale.AdminFrame.confirm(
				BX.message("SCALE_PANEL_JS_ADVICE_TO_BACKUP"),
				BX.message("SCALE_PANEL_JS_ADVICE_TO_BACKUP_TITLE"),
				function(){ window.location.href = "/bitrix/admin/dump.php?lang="+BX.message('LANGUAGE_ID'); },
				function() { BX.Scale.actionsCollection.getObject( _this.id ).start(serverHostname, paramsValues, true); }
				);

				return;
		}

		var userParams = this.getUserParams();
		var freeParams = {};

		this.currentOperation = "start"

		if(paramsValues !== undefined)
		{
			for(var key in paramsValues)
			{
				if(userParams && userParams[key] !== undefined)
					userParams[key].DEFAULT_VALUE = paramsValues[key];
				else
					this.freeParams[key] = paramsValues[key];
			}
		}

		if(userParams !== undefined)
		{
			this.paramsDialog = new BX.Scale.ActionParamsDialog({
				title: this.name,
				userParams: userParams,
				serverHostname: serverHostname,
				callback: this.sendRequest,
				context: this
			});

			this.paramsDialog.show();
		}
		else
		{
			BX.Scale.AdminFrame.confirm(
				BX.message("SCALE_PANEL_JS_ACT_CONFIRM")+" "+this.name+"?",
				BX.message("SCALE_PANEL_JS_ACT_CONFIRM_TITLE"),
				BX.proxy(function(){ this.sendRequest({ serverHostname: serverHostname, freeParams: freeParams }); }, this)
			);
		}
	};

	/**
	 * Shows the action execution results
	 * @param result
	 */
	BX.Scale.Action.prototype.showResultDialog = function(result)
	{
		var resultDialog = new BX.Scale.ActionResultDialog({
			actionName: this.name,
			result: result,
			pageRefresh: this.pageRefresh
		});

		resultDialog.show();
	};

	/**
	 * Shows the dialog of the asyn action's  execution process
	 * @param {object} result -request result
	 */
	BX.Scale.Action.prototype.showAsyncDialog = function(result)
	{
		BX.Scale.ActionProcessDialog.addActionProcess(this.name);

		BX.Scale.AdminFrame.timeIntervalId = setInterval(BX.proxy(this.checkAsyncState, this), BX.Scale.AdminFrame.timeAsyncRefresh);

		if( result.ACTION_RESULT
			&& result.ACTION_RESULT[this.id]
			&& result.ACTION_RESULT[this.id].OUTPUT
			&& result.ACTION_RESULT[this.id].OUTPUT.DATA
		)
		{
			if(result.ACTION_RESULT[this.id].OUTPUT.DATA.message)
				BX.Scale.ActionProcessDialog.addActionMessage(result.ACTION_RESULT[this.id].OUTPUT.DATA.message);

			if(result.ACTION_RESULT[this.id].OUTPUT.DATA.params)
			{
				for(var i in result.ACTION_RESULT[this.id].OUTPUT.DATA.params)
				{
					BX.Scale.AdminFrame.currentAsyncActionBID = i;
					break;
				}
			}
		}

		BX.Scale.ActionProcessDialog.pageRefresh = this.pageRefresh;
		BX.Scale.ActionProcessDialog.show();

		if(BX.Scale.AdminFrame.currentAsyncActionBID.length <= 0)
		{
			BX.Scale.ActionProcessDialog.addActionMessage(result.ACTION_RESULT[this.id].OUTPUT.TEXT);
			BX.Scale.ActionProcessDialog.setActionResult(false, BX.message("SCALE_PANEL_JS_BID_ERROR"));
		}
	};

	/**
	 * Form request params to execute action
	 * @param {object} params - action params
	 */
	BX.Scale.Action.prototype.checkAsyncState = function()
	{
		if(BX.Scale.AdminFrame.currentAsyncActionBID.length <= 0 )
			return false;

		var sendPrams = {
				operation: "check_state",
				bid: BX.Scale.AdminFrame.currentAsyncActionBID
			};


		var callbacks = {
			onsuccess: function(result){
				BX.Scale.AdminFrame.failureAnswersCount = 0;

				if(result)
				{
					if(result.ERROR.length <= 0 && result.ACTION_STATE && result.ACTION_STATE.status)
					{
						if(result.ACTION_STATE.status == "finished")
						{
							clearInterval(BX.Scale.AdminFrame.timeIntervalId );
							BX.Scale.ActionProcessDialog.setActionResult(true, BX.message("SCALE_PANEL_JS_ACT_EXEC_SUCCESS"));
							BX.Scale.AdminFrame.currentAsyncActionBID = "";
						}
						else if(result.ACTION_STATE.status == "error")
						{
							clearInterval(BX.Scale.AdminFrame.timeIntervalId );

							var mess = "";

							if(result.ACTION_STATE.error_messages)
							{
								for(var i in result.ACTION_STATE.error_messages)
								{
									mess += result.ACTION_STATE.error_messages[i]+"<br>";
								}
							}

							BX.Scale.ActionProcessDialog.setActionResult(false, mess);
							BX.Scale.AdminFrame.currentAsyncActionBID = "";
						}

					}
					else if(!result.ACTION_STATE || result.ACTION_STATE.status)
					{
						clearInterval(BX.Scale.AdminFrame.timeIntervalId );
						BX.Scale.ActionProcessDialog.setActionResult(false);
					}
					else
					{
						clearInterval(BX.Scale.AdminFrame.timeIntervalId );
						BX.Scale.ActionProcessDialog.setActionResult(false, BX.message("SCALE_PANEL_JS_ERROR")+" "+result.ERROR);
					}
				}
				else
				{
					if(BX.Scale.AdminFrame.failureAnswersCountAllow >= BX.Scale.AdminFrame.failureAnswersCount)
					{
						BX.Scale.AdminFrame.failureAnswersCount++;
						return;
					}

					clearInterval(BX.Scale.AdminFrame.timeIntervalId );
					BX.Scale.ActionProcessDialog.setActionResult(false, BX.message("SCALE_PANEL_JS_ACT_EXEC_ERROR"));
				}
			},
			onfailure: function(){

				if(BX.Scale.AdminFrame.failureAnswersCountAllow >= BX.Scale.AdminFrame.failureAnswersCount)
				{
					BX.Scale.AdminFrame.failureAnswersCount++;
					return;
				}

				clearInterval(BX.Scale.AdminFrame.timeIntervalId );
				BX.Scale.AdminFrame.alert(
					BX.message("SCALE_PANEL_JS_ACT_RES_ERROR"),
					BX.message("SCALE_PANEL_JS_ERROR")
				);
			}
		};

		BX.Scale.Communicator.sendRequest(sendPrams, callbacks, this, false);

		return true;
	};
		/**
	 * Form request params to execute action
	 * @param {object} params - action params
	 */
	BX.Scale.Action.prototype.sendRequest = function(params)
	{
		var sendPrams = {
				actionId: this.id,
				serverHostname: params.serverHostname,
				operation: this.currentOperation
			},
			_this = this;

		if(params.userParams !== undefined)
			sendPrams.userParams = params.userParams;

		if(this.freeParams)
			sendPrams.freeParams = this.freeParams;

		var callbacks = {
			onsuccess: function(result){

				if(result)
				{
					if(result.ERROR.length <= 0)
					{
						if(this.async)
						{
							_this.showAsyncDialog(result);
						}
						else
						{
							if(result.ACTION_RESULT
								&& result.ACTION_RESULT.COPY_KEY_TO_SERVER
								&& result.ACTION_RESULT.COPY_KEY_TO_SERVER.RESULT == "ERROR"
								&& result.ACTION_RESULT.COPY_KEY_TO_SERVER.OUTPUT.DATA.message.search(/^User must change password/) != -1
								)
							{
								BX.Scale.AdminFrame.alert(
									BX.message("SCALE_PANEL_JS_PASS_MUST_BE_CHANGED"),
									BX.message("SCALE_PANEL_JS_WARNING"),
									function(){
										BX.Scale.actionsCollection.getObject("CHANGE_PASSWD_FIRST").start(sendPrams.serverHostname, sendPrams.userParams);
										BX.Scale.AdminFrame.nextActionId = "NEW_SERVER_CHAIN";
									}
								);
							}
							else
							{
								if(BX.Scale.AdminFrame.nextActionId != this.id
									&& BX.Scale.AdminFrame.nextActionId !== null
									&& BX.Scale.actionsCollection.getObject(BX.Scale.AdminFrame.nextActionId)
									)
								{
									BX.Scale.actionsCollection.getObject(BX.Scale.AdminFrame.nextActionId).start(sendPrams.serverHostname, sendPrams.userParams);
									BX.Scale.AdminFrame.nextActionId = null;
								}

								_this.showResultDialog(result);
							}
						}
					}
					else
					{
						BX.Scale.AdminFrame.alert(
							result.ERROR,
							BX.message("SCALE_PANEL_JS_ERROR")
						);

					}
				}
				else
				{
					BX.Scale.AdminFrame.alert(
						BX.message("SCALE_PANEL_JS_ACT_EXEC_ERROR"),
						BX.message("SCALE_PANEL_JS_ERROR")
					);
				}
			},
			onfailure: function(){
				BX.Scale.AdminFrame.alert(
					BX.message("SCALE_PANEL_JS_ACT_RES_ERROR"),
					BX.message("SCALE_PANEL_JS_ERROR")
				);
			}
		};

		BX.Scale.Communicator.sendRequest(sendPrams, callbacks, this, true);
	};

})(window);
