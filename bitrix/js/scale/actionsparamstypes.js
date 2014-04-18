/**
 * Classes
 * BX.Scale.ActionsParamsTypes.Proto
 * BX.Scale.ActionsParamsTypes.String
 * BX.Scale.ActionsParamsTypes.Checkbox
 * BX.Scale.ActionsParamsTypes.Listbox
 */

 ;(function(window) {

	if (BX.Scale.ActionsParamsTypes) return;
		BX.Scale.ActionsParamsTypes = {};

	/**
	 * Class BX.Scale.ActionsParamsTypes.Proto
	 * Abstract class for user params.
	 * @param paramId
	 * @param params
	 * @constructor
	 */
	BX.Scale.ActionsParamsTypes.Proto = {

		init: function(paramId, params)
		{
			this.id = paramId;
			this.domNodeId = "action_user_param_"+paramId;
			this.domNode = null;
			this.name = params.NAME;
			this.defaultValue = params.DEFAULT_VALUE;
			this.required = params.REQUIRED;
			this.type = params.TYPE
		},

		/**
		 * Absract function generates HTML for UI
		 */

		/**
		 * Absract function generates DOM node
		 */
		createDomNode: function(){},

		/**
		 *  @returns {domNode}
		 */
		getDomNode: function()
		{
			return this.domNode;
		},

		/**
		 * Function returns entered by user value
		 */
		getValue: function()
		{
			var result = false;

			if(this.domNode && this.domNode.value !== undefined)
				result = this.domNode.value

			return result;
		}
	};

	/**
	 * Class BX.Scale.ActionsParamsTypes.String
	 */

	BX.Scale.ActionsParamsTypes.String = function(paramId, params)
	{
		this.init(paramId, params);

		this.createDomNode = function()
		{
			var type = this.type == "PASSWORD" ? "password" : "text";

			this.domNode = BX.create('INPUT', {props: {id: this.domNodeId, name: this.domNodeId, type: type}});

			if(this.defaultValue !== undefined)
				this.domNode.value = this.defaultValue;

			if(this.required !== undefined && this.required == "Y")
			{
				var _this = this;
				this.domNode.onkeyup = function(e){
					var empty = _this.isEmpty();
					BX.onCustomEvent("BXScaleActionParamKeyUp", [{paramId: _this.id, empty: empty }]);
				}
			}
		};

		this.isEmpty = function()
		{
			return (this.domNode.value.length <= 0);
		};

		this.createDomNode();
	};

	BX.Scale.ActionsParamsTypes.String.prototype = BX.Scale.ActionsParamsTypes.Proto;

	/**
	 * Class BX.Scale.ActionsParamsTypes.Checkbox
	 */
	BX.Scale.ActionsParamsTypes.Checkbox = function(paramId, params)
	{
		this.init(paramId, params);
		this.checked = params.CHECKED == "Y" || this.defaultValue == "Y";
		this.string = params.STRING || "";

		this.createDomNode = function()
		{
			this.domNode = BX.create('INPUT', {props: {id: this.domNodeId, name: this.domNodeId, type: 'checkbox', checked: this.checked}});
		};

		this.getValue = function()
		{
			var domNode = BX(this.domNodeId),
				result = false;

			if(domNode && domNode.checked !== undefined)
				result = domNode.checked ? this.string : "";

			return result;
		};

		this.createDomNode();
	};

	BX.Scale.ActionsParamsTypes.Checkbox.prototype = BX.Scale.ActionsParamsTypes.Proto;

	/**
	 * Creates action param as drop down list
	 * @param paramId
	 * @param params
	 */
	BX.Scale.ActionsParamsTypes.Listbox = function(paramId, params)
	{
		this.init(paramId, params);
		this.values = params.VALUES;

		this.getHtml = function()
		{
			var result = "";
			result += this.name+": <select name=\""+this.domNodeId+"\" id=\""+this.domNodeId+"\">";

			for(var key in this.values)
				result += "<option value=\""+key+"\">"+this.values[key]+"</option>";
			result += "</select>";

			result = this.setRequired(result);

			return result;
		};
	};

	BX.Scale.ActionsParamsTypes.Listbox.prototype = BX.Scale.ActionsParamsTypes.Proto;

	/**
	 * Class BX.Scale.ActionsParamsTypes.Text
	 */

	BX.Scale.ActionsParamsTypes.Text = function(paramId, params)
	{
		this.init(paramId, params);

		this.createDomNode = function()
		{
			this.domNode = BX.create('DIV');
			this.textNode = BX.create('SPAN', {html: this.defaultValue});
			this.inputNode = BX.create('INPUT', {props: {id: this.domNodeId, name: this.domNodeId, type: "hidden"}});

			if(this.defaultValue !== undefined)
				this.inputNode.value = this.defaultValue;

			this.domNode.appendChild(this.inputNode);
			this.domNode.appendChild(this.textNode);
		};

		this.getValue =  function()
		{
			var result = false;

			if(this.inputNode && this.inputNode.value !== undefined)
				result = this.inputNode.value

			return result;
		}

		this.createDomNode();
	};

	BX.Scale.ActionsParamsTypes.Text.prototype = BX.Scale.ActionsParamsTypes.Proto;

})(window);