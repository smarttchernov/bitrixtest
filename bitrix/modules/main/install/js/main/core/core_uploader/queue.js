;(function(window){
	if (window.BX["UploaderQueue"])
		return false;
	var BX = window.BX;

	var statuses = BX.UploaderUtils.statuses;

		/**
	 * @return {BX.UploaderQueue}
	 * @params array
	 * @params[form] - DOM-node
	 * @params[placeHolder] - DOM node to append files /OL or UL/
	 */
	BX.UploaderQueue = function (params, limits, caller)
	{
		this.dialogName = "BX.UploaderQueue";
		limits = (!!limits ? limits : {});

		this.limits = {
			phpPostMaxSize : parseInt(BX.message('phpPostMaxSize')),
			phpUploadMaxFilesize : parseInt(BX.message('phpUploadMaxFilesize')),
			uploadMaxFilesize : (limits["uploadMaxFilesize"] > 0 ? limits["uploadMaxFilesize"] : 0),
			uploadFileWidth : (limits["uploadFileWidth"] > 0 ? limits["uploadFileWidth"] : 0),
			uploadFileHeight : (limits["uploadFileHeight"] > 0 ? limits["uploadFileHeight"] : 0)};

		this.placeHolder = BX(params["placeHolder"]);

		this.uploader = caller;
		this.itForUpload = new BX.UploaderUtils.Hash();
		this.items = new BX.UploaderUtils.Hash();
		this.itUploaded = new BX.UploaderUtils.Hash();
		this.itFailed = new BX.UploaderUtils.Hash();
		this.thumb = { tagName : "LI", className : "bx-bxu-thumb-thumb"};
		if (!!params["thumb"])
		{
			for (var ii in params["thumb"])
			{
				if (params["thumb"].hasOwnProperty(ii))
				{
					this.thumb[ii] = params["thumb"][ii];
				}
			}
		}

		BX.addCustomEvent(caller, "onItemIsAdded", BX.delegate(this.addItem, this));
		BX.addCustomEvent(caller, "onItemsAreAdded", BX.delegate(this.finishQueue, this));

		BX.addCustomEvent(caller, "onFileIsDeleted", BX.delegate(this.deleteItem, this));

		this.log('Initialized');
		return this;
	};
	BX.UploaderQueue.prototype = {
		showError : function(text) { this.log('Error! ' + text); },
		log : function(text)
		{
			BX.UploaderUtils.log('queue', text);
		},
		addItem : function (file) {

			var params = {copies : this.uploader.fileCopies, fields : this.uploader.fileFields},
				res = (!!file["type"] && file.type.indexOf("image/") === 0 ?
					new BX.UploaderImage(file, params, this.limits, this.uploader) :
					new BX.UploaderFile(file, params, this.limits, this.uploader));

			if (!!this.placeHolder)
			{
				var node = BX.create(this.thumb.tagName, {
					attrs : {
						id : res.id + 'Item',
						'bx-bxu-item-id' : res.id,
						className : this.thumb.className},
					children : [res.makeThumb()]
				});
				if (!!window["jsDD"])
				{
					if (!this._onbxdragstart)
					{
						this._onbxdragstart = BX.delegate(this.onbxdragstart, this);
						this._onbxdragstop = BX.delegate(this.onbxdragstop, this);
						this._onbxdrag = BX.delegate(this.onbxdrag, this);
						this._onbxdraghout = BX.delegate(this.onbxdraghout, this);
						this._onbxdestdraghover = BX.delegate(this.onbxdestdraghover, this);
						this._onbxdestdraghout = BX.delegate(this.onbxdestdraghout, this);
						this._onbxdestdragfinish = BX.delegate(this.onbxdestdragfinish, this);
					}
					BX.addClass(node, "bx-drag-draggable");
					node.onbxdragstart = this._onbxdragstart;
					node.onbxdragstop = this._onbxdragstop;
					node.onbxdrag = this._onbxdrag;
					node.onbxdraghout = this._onbxdraghout;
					window.jsDD.registerObject(node);

					node.onbxdestdraghover = this._onbxdestdraghover;
					node.onbxdestdraghout = this._onbxdestdraghout;
					node.onbxdestdragfinish = this._onbxdestdragfinish;
					window.jsDD.registerDest(node);
				}
				this.placeHolder.appendChild(node);
			}
			this.itForUpload.setItem(res.id, res);
			this.items.setItem(res.id, res);

			BX.onCustomEvent(this.uploader, "onQueueIsChanged", [this, "add", res.id, res]);
		},
		getItem : function(id)
		{
			return {item : this.items.getItem(id), node : BX(id + 'Item')};
		},
		onbxdragstart : function() {
			var item = BX.proxy_context,
				div = BX.create('DIV', {
				attrs : {
					className : "bx-drag-object"
				},
				style : {
					position : "absolute",
					zIndex : 10,
					width : item.clientWidth + 'px'
				},
				html : item.innerHTML
			});
			item.__dragCopyDiv = div;

			document.body.appendChild(item.__dragCopyDiv);
			BX.addClass(item, "bx-drag-source");
			return true;
		},
		onbxdragstop : function(x, y) {
			var item = BX.proxy_context;
			BX.removeClass(item, "bx-drag-source");
			if (!!item.__dragCopyDiv)
			{
				item.__dragCopyDiv.parentNode.removeChild(item.__dragCopyDiv);
				item.__dragCopyDiv = null;
			}
			return true;
		},
		onbxdrag : function(x, y) {
			var item = BX.proxy_context, div = item.__dragCopyDiv;
			div.style.left = x + 'px';
			div.style.top = y + 'px';
		},
		onbxdraghout : function(currentNode, x, y) {
		},
		onbxdestdraghover : function(currentNode, x, y) {
			var item = BX.proxy_context, div = item.__dragCopyDiv, pos = BX.pos(item);
			BX.addClass(item, "bx-drag-over");
			return true;
		},
		onbxdestdraghout : function(currentNode, x, y) {
			var item = BX.proxy_context, div = item.__dragCopyDiv;
			BX.removeClass(item, "bx-drag-over");
			return true;
		},
		onbxdestdragfinish : function(currentNode, x, y) {
			var item = BX.proxy_context;
			BX.removeClass(item, "bx-drag-over");
			if(item == currentNode)
				return true;
			else
			{
				var obj = item.parentNode,
					n = obj.childNodes.length;

				for (var j=0; j<n; j++)
				{
					if (obj.childNodes[j] == item)
						item.number = j;
					else if (obj.childNodes[j] == currentNode)
						currentNode.number = j;

					if (currentNode.number > 0 && item.number > 0)
						break;
				}
				if (this.itForUpload.hasItem(currentNode.getAttribute("bx-bxu-item-id")))
				{
					var act = (item.number <= currentNode.number ? "beforeItem" : (
						item.nextSibling ? "afterItem" : "inTheEnd")), it = null;
					if (act != "inTheEnd")
					{
						for (j = item.number + (act == "beforeItem" ? 0 : 1); j < n; j++)
						{
							if (this.itForUpload.hasItem(obj.childNodes[j].getAttribute("bx-bxu-item-id")))
							{
								it = obj.childNodes[j].getAttribute("bx-bxu-item-id");
								break;
							}
						}
						if (it === null)
							act = "inTheEnd";
					}
					var buff = this.itForUpload.removeItem(currentNode.getAttribute("bx-bxu-item-id"));
					if (act != "inTheEnd")
						this.itForUpload.insertBeforeItem(buff.id, buff, it);
					else
						this.itForUpload.setItem(buff.id, buff);
				}

				currentNode.parentNode.removeChild(currentNode);
				if (item.number <= currentNode.number)
				{
					item.parentNode.insertBefore(currentNode, item);
				}
				else if (item.nextSibling)
				{
					item.parentNode.insertBefore(currentNode, item.nextSibling);
				}
				else
				{
					item.parentNode.appendChild(currentNode);
				}

			}
			return true;
		},
		deleteItem : function (id, item) {
			if (this.items.hasItem(id))
			{
				var node = BX(id + 'Item');
				if (!!window["jsDD"])
				{
					node.onmousedown = null;
					node.onbxdragstart = null;
					node.onbxdragstop = null;
					node.onbxdrag = null;
					node.onbxdraghout = null;
					node.onbxdestdraghover = null;
					node.onbxdestdraghout = null;
					node.onbxdestdragfinish = null;
					node.__bxpos = null;

					window.jsDD.arObjects[node.__bxddid] = null;
					delete window.jsDD.arObjects[node.__bxddid];

					window.jsDD.arDestinations[node.__bxddeid] = null;
					delete window.jsDD.arDestinations[node.__bxddeid];
				}
				BX.unbindAll(node);
				if (!!node)
					node.parentNode.removeChild(node);

				this.items.removeItem(id);
				this.itUploaded.removeItem(id);
				this.itFailed.removeItem(id);
				this.itForUpload.removeItem(id);
				BX.onCustomEvent(this.uploader, "onQueueIsChanged", [this, "add", id, item]);
				return true;
			}
			return false;
		},
		finishQueue : function()
		{
			this.makePreview(null);
		},
		clear : function()
		{
			var item;
			while ((item = this.items.getFirst()) && !!item)
				this.deleteItem(item.id, item);
		},
		makePreview : function(id) {
			var item = (id === null ? this.items.getNext() : this.items.getItem(id)), func = null;
			if (id === null)
			{
				func = this.makePreviewDelegate = (!!this.makePreviewDelegate ? this.makePreviewDelegate : BX.proxy(function() { this.makePreview(null); }, this));
			}
			if (item)
				item.makePreview(func);
			return true;
		},
		restoreFiles : function(IDs, restoreError)
		{
			restoreError = (restoreError === true);

			var item, tmp = {}, id, ii;
			for (ii = 0; ii < IDs.length; ii++)
			{
				id = IDs[ii];
				if (this.items.hasItem(id) && (item = this.items.getItem(id)) && !!item &&
					!this.itUploaded.hasItem(item.id) &&
					(!restoreError && !this.itFailed.hasItem(item.id) || restoreError))
				{
					tmp[item.id] = (tmp[item.id] > 0 ? tmp[item.id] : 0);
					tmp[item.id]++;
					this.itFailed.removeItem(item.id);
					this.itForUpload.setItem(item.id, item);
					BX.onCustomEvent(item, "onUploadRestore", [item]);
				}
			}
		}
	};
}(window));