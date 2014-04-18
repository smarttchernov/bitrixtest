;(function(window){
	if (window.BX["UploaderFile"])
		return false;
	var BX = window.BX;

	var statuses = BX.UploaderUtils.statuses,
		image = new Image(), ctx, canvas,
		reader;

	/**
	 * @return {BX.UploaderFile}
	 * @file file
	 * @params array
	 * @limits array
	 * @caller {BX.Uploader}
	 * You should work with params["fields"] in case you want to change visual part
	 */

	BX.UploaderFile = function (file, params, limits, caller)
	{
		this.dialogName = "BX.UploaderFile";
		this.file = file;
		this.id = 'file' + BX.UploaderUtils.getId();
		this.name = file.name;
		if (BX.type.isDomNode(file))
		{
			this.name = this.getFileNameOnly(file.value);
			if (/\[(.+?)\]/.test(file.name))
			{
				var tmp = /\[(.+?)\]/.exec(file.name);
				this.id = tmp[1];
			}
			this.file.bxuHandler = this;
		}
		this.preview = '<span id="' + this.id + 'Canvas" class="bx-bxu-canvas"></span>';
		this.nameWithoutExt = (this.name.lastIndexOf('.') > 0 ? this.name.substr(0, this.name.lastIndexOf('.')) : this.name);
		this.ext = this.name.substr(this.nameWithoutExt.length);
		this.size = BX.UploaderUtils.getFormattedSize(file.size);
		this.type = file.type;
		this.status = statuses["new"];
		this.limits = limits;
		this.caller = caller;
		this.fields = {
			thumb : {
				template : '<div class="someclass">#preview#<div>#name#</div><div>#size#</div>',
				editorTemplate : '<div class="someeditorclass"><div>#name#</div>',
				className : "bx-bxu-thumb-thumb",
				placeHolder : null
			},
			preview : {
				params : { width : 100, height : 100 },
				template : "#preview#",
				editorParams : { width : 1024, height : 860 },
				editorTemplate : '<span>#preview#</span>',
				className : "bx-bxu-thumb-preview",
				placeHolder : null,
				events : {
					click : BX.delegate(this.clickFile, this)
				}
			},
			name : {
				template : "#name#",
				editorTemplate : '<span><input type="text" name="name" value="#name#" /></span>',
				className : "bx-bxu-thumb-name",
				placeHolder : null
			},
			type : {
				template : "#type#",
				editorTemplate : '#type#',
				className : "bx-bxu-thumb-type",
				placeHolder : null
			}
		};
		if (!!params["fields"])
		{
			var ij, key;
			for (var ii in params["fields"])
			{
				if (params["fields"].hasOwnProperty(ii))
				{
					if (!!this.fields[ii])
					{
						for (ij in params["fields"][ii])
						{
							if (params["fields"][ii].hasOwnProperty(ij))
							{
								this.fields[ii][ij] = params["fields"][ii][ij];
							}
						}
					}
					else
						this.fields[ii] = params["fields"][ii];
					key = ii + '';
					if (key.toLowerCase() != "thumb" && key.toLowerCase() != "preview")
					{
						this[key.toLowerCase()] = (!!params["fields"][ii]["value"] ? params["fields"][ii]["value"] : "");
						this.log(key.toLowerCase() + ': ' + this[key.toLowerCase()]);
					}
				}
			}
		}

		return this;
	};
	BX.UploaderFile.prototype = {
		log : function(text)
		{
			BX.UploaderUtils.log('file ' + this.name, text);
		},
		getFileNameOnly : function (name) {
			var lastPathDelimiter = name.lastIndexOf("\\");
			if (lastPathDelimiter == -1) lastPathDelimiter = name.lastIndexOf("/");
			return name.substring(lastPathDelimiter+1);
		},
		makeThumb : function()
		{
			var template = this.fields.thumb.template, name, res, ii, events = {}, node, jj;
			for (ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii) &&
					this.fields[ii].template &&
					this.fields[ii].template.indexOf('#' + ii + '#') >= 0)
				{
					name = this.id + ii.toUpperCase().substr(0, 1) + ii.substr(1);
					node = this.setProps(ii, this[ii], true);
					template = template.replace('#' + ii + '#', '<span id="' + name + '" class="' + this.fields[ii]["className"] + '">' + node.html + '</span>');
					for (jj in node.events)
					{
						if (node.events.hasOwnProperty(jj))
						{
							events[jj] = node.events[jj];
						}
					}
					if (!!this.fields[ii].events)
						events[name] = this.fields[ii].events;
				}
			}
			template = template.replace(/\#id\#/gi, this.id);
			res = BX.create("DIV", {
				attrs : {
					id : (this.id + 'Thumb'),
					className : this.fields.thumb.className
				},
				events : this.fields.thumb.events,
				html : template}
			);
			this.__makeThumbEventsObj = events;
			this.__makeThumbEvents = BX.delegate(function(id)
			{
				var ii, jj;
				for (ii in events)
				{
					if (events.hasOwnProperty(ii) && BX(ii))
					{
						for (jj in events[ii])
						{
							if (events[ii].hasOwnProperty(jj))
							{
								BX.bind(BX(ii), jj, events[ii][jj]);
							}
						}
					}
				}
				this.__makeThumbEvents = null;
				delete this.__makeThumbEvents;
			}, this);
			BX.addCustomEvent(this, "onFileIsInited", this.__makeThumbEvents);

			if (BX.type.isDomNode(this.file))
				res.appendChild(this.file);
			return res;
		},
		checkProps : function()
		{
			var el2 = BX.UploaderUtils.FormToArray({elements : [BX.proxy_context]}), ii;
			for (ii in el2.data)
			{
				if (el2.data.hasOwnProperty(ii))
					this[ii] = el2.data[ii];
			}
		},
		setProps : function(name, val, bReturn)
		{
			if (typeof name == "string")
			{
				if (name == "size")
					val = BX.UploaderUtils.getFormattedSize(this.file.size);
				if (typeof this[name] != "undefined" && typeof this.fields[name] != "undefined")
				{
					this[name] = val;
					var template = this.fields[name].template.
							replace('#' + name + '#', (!!val ? val : '')).
							replace(/\#id\#/gi, this.id),
						fii, fjj, el, result = {html : template, events : {}};

					this.hiddenForm = (!!this.hiddenForm ? this.hiddenForm : BX.create("FORM", { style : { display : "none" } } ));
					this._checkProps = (!!this._checkProps ? this._checkProps : BX.delegate(this.checkProps, this));
					this.hiddenForm.innerHTML = template;
					if (this.hiddenForm.elements.length > 0)
					{
						for (fii = 0; fii < this.hiddenForm.elements.length; fii++)
						{
							el = this.hiddenForm.elements[fii];
							if (typeof this[el.name] != "undefined")
							{
								if (!el.hasAttribute("id"))
									el.setAttribute("id", this.id + name + BX.UploaderUtils.getId())
								result.events[el.id] = {
									blur : this._checkProps
								}

							}
						}
						result.html = this.hiddenForm.innerHTML;
					}
					if (BX(this.hiddenForm))
						BX.remove(this.hiddenForm);
					this.hiddenForm = null;
					delete this.hiddenForm;
					if (bReturn)
						return result;
					var node = this.getPH(name);
					if (!!node)
					{
						node.innerHTML = result.html;
						for (fii in result.events)
						{
							if (result.events.hasOwnProperty(fii))
							{
								for (fjj in result.events[fii])
								{
									if (result.events[fii].hasOwnProperty(fjj))
									{
										BX.bind(BX(fii), fjj, result.events[fii][fjj]);
									}
								}
							}
						}
					}
				}
			}
			else if (!!name)
			{
				var data = name;
				for (var ii in data)
				{
					if (this.fields.hasOwnProperty(ii) && ii !== "preview")
					{
						this.setProps(ii, data[ii]);
					}
				}
			}
		},
		getProps : function(name)
		{
			if (name == "canvas")
			{
				return BX(this.id + "ProperCanvas");
			}
			else if (typeof name == "string")
			{
				return this[name];
			}
			var data = {}, jj;
			for (var ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii) && (ii !== "preview" && ii !== "thumb"))
				{
					data[ii] = this[ii];
				}
			}
			if (!!this.copies)
			{
				data["canvases"] = {};
				for (ii in this.copies)
				{
					if (this.copies.hasOwnProperty(ii))
					{
						data["canvases"][ii] = {};
						for (jj in this.copies[ii])
						{
							if (this.copies[ii].hasOwnProperty(jj))
							{
								data["canvases"][ii][jj] = this.copies[ii][jj];
							}
						}
					}
				}
			}
			return data;
		},
		getThumbs : function()
		{
			return null;
		},
		getPH : function(name)
		{
			name = (typeof name === "string" ? name : "");
			name = name.toLowerCase();
			if (this.fields.hasOwnProperty(name))
			{
				var id = name.substr(0, 1).toUpperCase() + name.substr(1);
				this.fields[name]["placeHolder"] = BX(this.id  + id);
				return this.fields[name]["placeHolder"];
			}
			return null
		},
		clickFile : function (e)
		{
			return false;
		},
		makePreviewImageWork : function(image, e)
		{
			this.file.width = image.width;
			this.file.height = image.height;
			var res = BX.UploaderUtils.scaleImage(image, {width : this.limits["uploadFileWidth"], height : this.limits["uploadFileHeight"]}),
				res2 = BX.UploaderUtils.scaleImage(image, this.fields.preview.params),
				props = {
					props : { width : res.destin.width, height : res.destin.height },
					attrs : {
						className : (this.file.width > this.file.height ? "landscape" : "portrait"),
						"bx-bxu-html-preview-compression-ratio" : res2.coeff
					}
				};
			if (res2.coeff <= 0)
				props["style"] = { width : res2.destin.width + 'px', height : res2.destin.height + 'px'};
			if (!!this.canvas)
			{
				BX.adjust(this.canvas, props);

				ctx = this.canvas.getContext('2d');
				ctx.drawImage(image,
					res.source.x, res.source.y, res.source.width, res.source.height,
					res.destin.x, res.destin.y, res.destin.width, res.destin.height
				);
				if (res.bNeedCreatePicture)
					this.applyFile(this.canvas);

				if (!!window.URL)
					window.URL.revokeObjectURL(image.src);
				ctx = null;

				if (BX(this.id + 'Canvas'))
					BX(this.id + 'Canvas').appendChild(this.canvas);
				return this.canvas;
			}
			else if (BX(this.id + 'Canvas'))
			{
				props["props"]["src"] = image.src;
				var img = BX.create("IMG", props);
				BX(this.id + 'Canvas').appendChild(img);
				return img;
			}

		},
		makePreview: function(callback)
		{
			this.status = statuses.ready;
			BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
			BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);

			this.log('is initialized');
			if (typeof callback == "function")
				callback();
			if (!!this.caller.queue.placeHolder && !!this.file["fileType"] && this.file.fileType.indexOf("image/") === 0)
			{
				this._onFileHasGotPreview = BX.delegate(function(id, item){
					if (id == this.id)
					{
						var img = new Image();
						BX.bind(img, 'load', BX.proxy(function(e){
							img = this.makePreviewImageWork(img);
							BX.onCustomEvent(this, "onFileHasPreview", [item.id, item, img]);
							img = null;
						}, this));
						img.src = item.file.url;
						BX.removeCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
						BX.removeCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);
					}
				}, this);
				this._onFileHasNotGotPreview = BX.delegate(function(id, item){
					if (id == this.id)
					{
						BX.removeCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
						BX.removeCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);
					}
				}, this);
				BX.addCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
				BX.addCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);
				BX.onCustomEvent(this.caller, "onFileNeedsPreview", [this.id, this, this.caller]);
			}
		},
		deleteFile: function()
		{
			this.getThumbsCnv = null;
			delete this.getThumbsCnv;
			var ii, events = this.__makeThumbEventsObj, jj;
			for (ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii))
				{
					if (!!this.fields[ii]["placeHolder"])
					{
						this.fields[ii]["placeHolder"] = null;
						BX.unbindAll(this.fields[ii]["placeHolder"]);
						delete this.fields[ii]["placeHolder"];
					}
				}
			}

			for (ii in events)
			{
				if (events.hasOwnProperty(ii) && BX(ii))
				{
					BX.unbindAll(BX(ii));
				}
			}

			this.file = null;
			delete this.file;

			BX.remove(this.canvas);
			this.canvas = null;
			delete this.canvas;

			BX.onCustomEvent(this.caller, "onFileIsDeleted", [this.id, this, this.caller]);
			BX.onCustomEvent(this, "onFileIsDeleted", [this, this.caller]);
		}
	};
	BX.UploaderImage = function(file, params, limits, caller)
	{
		BX.UploaderImage.superclass.constructor.apply(this, arguments);
		this.dialogName = "BX.UploaderImage";
		this.isImage = true;
		this.copies = {};
		this.caller = caller;
		if (!!params["copies"])
		{
			var copies = params["copies"];
			for (var ii in copies)
			{
				if (!!copies[ii])
				{
					copies[ii]['width'] = parseInt(copies[ii]['width']);
					copies[ii]["height"] = parseInt(copies[ii]["height"]);
					if (copies[ii]['width'] > 0 && copies[ii]["height"] > 0)
						this.copies[ii] = {width : copies[ii]['width'], height : copies[ii]["height"]};
				}
			}
		}
		return this;
	};
	BX.extend(BX.UploaderImage, BX.UploaderFile);

	BX.UploaderImage.prototype.makePreview = function(callback)
	{
		BX.unbindAll(image);
		this.makePreviewImageLoadHandler = BX.proxy(function(){
			this.makePreviewImageWork(image);
			this.status = statuses.ready;
			BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
			BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);
			this.log('is initialized');
			if (typeof callback == "function")
				setTimeout(callback, 200);
			this.makePreviewImageLoadHandler = null;
			delete this.makePreviewImageLoadHandler;
		}, this);
		BX.bind(image, 'load', this.makePreviewImageLoadHandler);
		this.canvas = BX.create('CANVAS', {attrs : { id : this.id + "ProperCanvas" } } );
		if (!!window.URL)
		{
			image.src = window.URL.createObjectURL(this.file);
		}
		else
		{
			if (!reader)
				reader = new FileReader();
			this.__readerOnLoad = BX.delegate(function(e) {
				image.src = e.target.result;
				this.__readerOnLoad = null;
				delete this.__readerOnLoad;
			}, this);
			reader.onload = this.__readerOnLoad;
			reader.readAsDataURL(this.file);
		}

		return true;
	};

	BX.UploaderImage.prototype.checkPreview = function()
	{
		// TODO check preview
	};
	BX.UploaderImage.prototype.applyFile = function(cnv, params)
	{
		this.checkPreview();

		if (!!params && params.data )
			this.setProps(params.data);

		var dataURI = cnv.toDataURL(this.file.type);
		this.file = BX.UploaderUtils.dataURLToBlob(dataURI);
		this.file.name = this.name;
		this.file.width = cnv.width;
		this.file.height = cnv.height;
		this.setProps('size')
		this.status = statuses.changed;
	};
	BX.UploaderImage.prototype.clickFile = function(e) {
		if (!BX.CanvasEditor)
			return false;

		this.eFunc = {
			"apply" : BX.delegate(this.applyFile, this),
			"delete" : BX.delegate(this.deleteFile, this),
			"clear" : BX.delegate(function()
			{
				BX.removeCustomEvent(editor, "onApplyCanvas", this.eFunc["apply"]);
				BX.removeCustomEvent(editor, "onDeleteCanvas", this.eFunc["delete"]);
				BX.removeCustomEvent(editor, "onClose", this.eFunc["clear"]);
			}, this)
		};

		var template = this.fields.thumb.editorTemplate, name;
		for (var ii in this.fields)
		{
			if (this.fields.hasOwnProperty(ii))
			{
				name = ii.substr(0, 1).toUpperCase() + ii.substr(1);
				template = template.replace('#' + ii + '#',
					(ii === "preview" ? "" :
						('<span id="' + this.id + name + 'Editor" class="' + this.fields[ii]["className"] + '">' +
						this.fields[ii]["editorTemplate"].replace('#' + ii + '#', (!!this[ii] ? this[ii] : '')) + '</span>')));
			}
		}

		var editor = BX.CanvasEditor.show(this.canvas, {title : this.name, template : template});
		BX.addCustomEvent(editor, "onApplyCanvas", this.eFunc["apply"]);
		BX.addCustomEvent(editor, "onDeleteCanvas", this.eFunc["delete"]);
		BX.addCustomEvent(editor, "onClose", this.eFunc["clear"]);
	};
	BX.UploaderImage.prototype.getThumbs = function(name) {
		var res = null;
		if (!this.getThumbsParams)
		{
			this.getThumbsParams = {canvases : [], number : null};
			for (var ii in this.copies)
			{
				if (this.copies.hasOwnProperty(ii))
				{
					this.getThumbsParams.canvases.push(ii);
				}
			}
		}
		var canvasProps, canvasPackageProps = {};
		if (typeof name !== "string")
		{
			if (this.getThumbsParams.number === null)
				this.getThumbsParams.number = 0
			else
				this.getThumbsParams.number++;
			if (this.getThumbsParams.number >= this.getThumbsParams.canvases.length)
				this.getThumbsParams.canvases.length = null;
			canvasPackageProps.packages = this.getThumbsParams.canvases.length;
			canvasPackageProps.package = this.getThumbsParams.number;
			name = this.getThumbsParams.canvases[this.getThumbsParams.number];
		}
		else if (name == "getCount")
			return this.getThumbsParams.canvases.length;

		canvasProps = this.copies[name];
		if (!!canvasProps)
		{
			if (!this.getThumbsCnv)
			{
				this.getThumbsCnv = BX.create('CANVAS', {
					props : { width : this.canvas.width, height : this.canvas.height },
					style : { width : this.canvas.width + 'px', height : this.canvas.height + 'px' } } );
				this.getThumbsCtx = this.getThumbsCnv.getContext("2d");
				this.getThumbsCtx.drawImage(this.canvas, 0, 0);
			}

			this.getThumbsCtx.save();
			var ratio = BX.UploaderUtils.scaleImage(this.canvas, this.copies[name], "inscribed");
			this.getThumbsCnv.width =  ratio.destin.width;
			this.getThumbsCnv.height = ratio.destin.height;
			this.getThumbsCtx.scale(ratio.coeff, ratio.coeff);
			this.getThumbsCtx.drawImage(this.canvas, 0, 0);
			var dataURI = this.getThumbsCnv.toDataURL(this.file.type);

			res = BX.UploaderUtils.dataURLToBlob(dataURI);
			res.width = this.getThumbsCnv.width;
			res.height = this.getThumbsCnv.height;

			this.getThumbsCtx.restore();

			res.name = this.name;
			res.thumb = name;
			if (canvasPackageProps["packages"] > 0)
			{
				res.canvases = canvasPackageProps.packages;
				res.canvas = canvasPackageProps.package;
			}
		}
		return res;
	};

}(window));