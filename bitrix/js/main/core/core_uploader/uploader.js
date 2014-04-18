;(function(window){
	if (window.BX["Uploader"])
		return false;
	var BX = window.BX;
	var statuses = BX.UploaderUtils.statuses;
	/**
	 * @return {BX.Uploader}
	 * @params array
	 * @params[input] - BX(id).
	 *  DOM-node with id="uploader_somehash" should exist and will be replaced	 *
	 * @params[dropZone] - DOM node to drag&drop
	 * @params[placeHolder] - DOM node to append files
	 *
	 */
	BX.Uploader = function(params)
	{
		var ii;
		if (!(typeof params == "object" && !!params && BX(params["input"])))
		{
			BX.debug(BX.message("UPLOADER_INPUT_IS_NOT_DEFINED"));
			return false;
		}
		this.fileInput = BX(params["input"]);
		this.dialogName = "BX.Uploader";
		this.id = (!!params["id"] ? params["id"] : Math.random());
		this.CID = (!!params["CID"] ? !!params["CID"] : ("CID" + BX.UploaderUtils.getId()));
		this.streams = new BX.UploaderUtils.Hash();
		params['streams'] = (params['streams'] > 0 ? params['streams'] : 1);
		for (ii = 0; ii < params['streams']; ii++)
			this.streams.setItem('stream' + ii, {id : 'stream' + ii});

		// Limits
		this.limits = {
			phpMaxFileUploads : parseInt(BX.message('phpMaxFileUploads')),
			phpPostMaxSize : parseInt(BX.message('phpPostMaxSize')),
			phpUploadMaxFilesize : parseInt(BX.message('phpUploadMaxFilesize')),
			uploadMaxFilesize : (params["uploadMaxFilesize"] > 0 ? params["uploadMaxFilesize"] : 0),
			uploadFileWidth : (params["uploadFileWidth"] > 0 ? params["uploadFileWidth"] : 0),
			uploadFileHeight : (params["uploadFileHeight"] > 0 ? params["uploadFileHeight"] : 0),
			allowUpload : ((params["allowUpload"] == "A" || params["allowUpload"] == "I" || params["allowUpload"] == "F") ? params["allowUpload"] : "A"),
			allowUploadExt : (typeof params["allowUploadExt"] === "string" ? params["allowUploadExt"] : "")};
// ALLOW_UPLOAD = 'A'll files | 'I'mages | 'F'iles with selected extensions
// ALLOW_UPLOAD_EXT = comma-separated list of allowed file extensions (ALLOW_UPLOAD='F')
		this.limits["uploadFile"] = (params["allowUpload"] == "I" ? "image/*" : "");
		this.limits["uploadFileExt"] = this.limits["allowUploadExt"];

		if (this.limits["uploadFileExt"].length > 0)
		{
			var ext = this.limits["uploadFileExt"].split(this.limits["uploadFileExt"].indexOf(",") >= 0 ? "," : " ");
			for (ii = 0; ii < ext.length; ii++)
				ext[ii] = (ext[ii].charAt(0) == "." ? ext[ii].substr(1) : ext[ii]);
			this.limits["uploadFileExt"] = ext.join(",");
		}
		this.params = params;

		this.params["filesInputName"] = (!!this.fileInput["name"] ? this.fileInput["name"] : "FILES");
		this.params["filesInputMultiple"] = (!!this.fileInput["multiple"] || this.params["filesInputMultiple"] ? "multiple" : false);
		this.params["uploadFormData"] = (this.params["uploadFormData"] == "N" ? "N" : "Y");
		this.params["uploadMethod"] = (this.params["uploadMethod"] == "immediate" ? "immediate" : "deferred");
		this.asynch = (this.params["uploadMethod"] == "immediate");

		this.params["imageExt"] = "jpg,bmp,jpeg,jpe,gif,png";
		this.params["uploadInputName"] = (!!this.params["uploadInputName"] ? this.params["uploadInputName"] : "bxu_files");
		this.params["uploadInputInfoName"] = (!!this.params["uploadInputInfoName"] ? this.params["uploadInputInfoName"] : "bxu_info");


		if (this.init(this.fileInput)) // init fileInput
		{
			if (!!params["dropZone"])
				this.initDropZone(BX(params["dropZone"]));

			this.form = this.fileInput.form;
			if (!!params["events"])
			{
				for(ii in params["events"])
				{
					if (params["events"].hasOwnProperty(ii))
					{
						BX.UploaderUtils.bindEvents(this, ii, params["events"][ii]);
					}
				}
			}

			this.uploadFileUrl = (!!params["uploadFileUrl"] ? params["uploadFileUrl"] : this.form.action);
			if (!this.uploadFileUrl || this.uploadFileUrl.length <= 0)
			{
				BX.debug(BX.message("UPLOADER_ACTION_URL_NOT_DEFINED"));
			}
			this.status = statuses.ready;


			/* This params only for files. They are here for easy way to change them */
			this.fileFields = params["fields"];
			this.fileCopies = params["copies"];
			this.queue = new BX.UploaderQueue({placeHolder : BX(params["placeHolder"]), thumb : params["thumb"]}, this.limits, this);

			this.params["doWeHaveStorage"] = !!BX(params["placeHolder"]);
			BX.addCustomEvent(this, 'done', BX.delegate(function(id, item){
				if (BX(this.fileInput.form) && !this.fileInput.form.elements[this.params["uploadInputInfoName"] + "[CID]"])
					this.fileInput.form.appendChild(BX.create("INPUT", {props : { type : "hidden", name : this.params["uploadInputInfoName"] + "[CID]", value : this.CID }}));
				this.init(this.fileInput);
			}, this));
			if (!!this.params["filesInputName"])
			{
				BX.addCustomEvent(this, 'onFileIsUploaded', BX.delegate(function(id, item){
					var node = BX.create("INPUT", {props : { type : "hidden", name : this.params["filesInputName"] + '[]', value : item.hash }});
					if (BX(params["placeHolder"]) && BX(id + 'Item'))
						BX(id + 'Item').appendChild(node);
					else
						this.fileInput.parentNode.insertBefore(node, this.fileInput);
				}, this));
			}

			BX.addCustomEvent(this, 'onFileIsDeleted', BX.delegate(function(id, file){
				if (!!file && !!file.hash)
				{
					var data = this.preparePost({mode : "delete", hash : file.hash}, false);
					BX.ajax.get(
						this.uploadFileUrl,
						data.data
					);
				}
			}, this));
			BX.onCustomEvent(window, "onUploaderIsInited", [this.id, this]);
			return this;
		}
	};

	BX.Uploader.prototype = {
		init : function(fileInput)
		{
			this.log('Initialized');
			if (fileInput == this.fileInput)
				fileInput = this.fileInput = this.mkFileInput(fileInput);
			else
				fileInput = this.mkFileInput(fileInput);

			if (fileInput)
			{
				BX.bind(fileInput, "change", BX.delegate(this.onChange, this));
				return true;
			}
			return false;
		},
		log : function(text)
		{
			BX.UploaderUtils.log('uploader', text);
		},
		initDropZone : function(node)
		{
			if (!!BX.DD && BX.type.isDomNode(node) && node.parentNode)
			{
				this.dropZone = new BX.DD.dropFiles(node);
				if (this.dropZone && this.dropZone.supported() && BX.ajax.FormData.isSupported()) {
					BX.addCustomEvent(this.dropZone, 'dropFiles', BX.delegate(this.onChange, this));
					BX.addCustomEvent(this.dropZone, 'dragEnter', BX.delegate(this.ddDragEnter, this));
					BX.addCustomEvent(this.dropZone, 'dragLeave', BX.delegate(this.ddDragLeave, this));
				}
			}
		},
		ddDragEnter : function() { BX.addClass(this.dropZone.DIV, "bxu-file-input-over"); },
		ddDragLeave : function() { BX.removeClass(this.dropZone.DIV, "bxu-file-input-over"); },
		onChange : function(fileInput)
		{
			var files = fileInput, ext;
			if (!!fileInput && !!fileInput.target)
				files = fileInput.target.files;
			else if (!fileInput)
				files = this.fileInput.files;
			if (typeof files !== "undefined" && files.length > 0)
			{
				BX.PreventDefault(fileInput);
				this.init(fileInput);
				if (!this.params["doWeHaveStorage"])
					this.queue.clear();

				var added = false;
				for (var i=0, f; i < files.length; i++)
				{
					f = files[i];
					if (f.type === "")
					{
						continue;
					}
					else if (this.limits["uploadFileExt"].length > 0)
					{
						ext = (f.name || '').split('.').pop();
						if (this.limits["uploadFileExt"].indexOf(ext) < 0)
							continue;
					}
					BX.onCustomEvent(this, "onItemIsAdded", [f, this]);
					added = true;
				}
				if (added)
				{
					BX.onCustomEvent(this, "onItemsAreAdded", [this]);
					if (this.asynch)
						this.submit(true);
				}
			}
			return false;
		},
		mkFileInput : function(oldNode)
		{
			if (!BX(oldNode))
				return false;
			BX.unbindAll(oldNode);
			var node = oldNode.cloneNode(true);
			BX.adjust(node, {
				attrs: {
					name: (this.params["uploadInputName"] + '[]'),
					multiple : this.params["filesInputMultiple"],
					accept : this.limits["uploadFile"],
					value : ""
			}});
			oldNode.parentNode.insertBefore(node, oldNode);
			oldNode.parentNode.removeChild(oldNode);

			return node;
		},
		checkFile : function(item)
		{
			var error = "";
			if (this.limits["uploadMaxFilesize"] > 0 && item.file && item.file.size > this.limits["uploadMaxFilesize"])
			{
				error = BX.message('FILE_BAD_SIZE') + '(' + BX.UploaderUtils.getFormattedSize(this.limits["uploadMaxFilesize"], 2) + ')';
			}
			return error;
		},
		appendToForm : function(fd, key, val)
		{
			if (!!val && typeof val == "object")
			{
				for (var ii in val)
				{
					if (val.hasOwnProperty(ii))
					{
						this.appendToForm(fd, key + '[' + ii + ']', val[ii]);
					}
				}
			}
			else
				fd.append(key, (!!val ? val : ''));
		},
		prepareData : function(arData)
		{
			var data = {};
			if (null != arData)
			{
				if(typeof arData == 'object')
				{
					for(var i in arData)
					{
						if (arData.hasOwnProperty(i))
						{
							var name = BX.util.urlencode(i);
							if(typeof arData[i] == 'object')
								data[name] = this.prepareData(arData[i]);
							else
								data[name] = BX.util.urlencode(arData[i]);
						}
					}
				}
				else
					data = BX.util.urlencode(arData);
			}
			return data;
		},
		preparePost : function(data, prepareForm)
		{
			if (prepareForm === true && this.params["uploadFormData"] == "Y" && !this.post)
			{
				var post2 = {"AJAX_POST" : "Y", "sessid" : BX.bitrix_sessid()};
				post2 = BX.UploaderUtils.FormToArray(this.form, post2);
				if (!!post2.data[this.params["filesInputName"]])
				{
					post2.data[this.params["filesInputName"]] = null;
					delete post2.data[this.params["filesInputName"]];
				}
				if (!!post2.data[this.params["uploadInputInfoName"]])
				{
					post2.data[this.params["uploadInputInfoName"]] = null;
					delete post2.data[this.params["uploadInputInfoName"]];
				}
				if (!!post2.data[this.params["uploadInputName"]])
				{
					post2.data[this.params["uploadInputName"]] = null;
					delete post2.data[this.params["uploadInputName"]];
				}
				post2.size = BX.UploaderUtils.sizeof(post2.data);
				this.post = post2;
			}
			var post = (prepareForm === true && this.params["uploadFormData"] == "Y" ? this.post : {data : {"AJAX_POST" : "Y", "sessid" : BX.bitrix_sessid()}, size : 48}), size = 0;
			if (!!data)
			{
				post.data[this.params["uploadInputInfoName"]] = {
					CID : this.CID
				};
				for (var ii in data)
				{
					if (data.hasOwnProperty(ii))
					{
						post.data[this.params["uploadInputInfoName"]][ii] = data[ii];
					}
				}
				size = BX.UploaderUtils.sizeof(this.params["uploadInputInfoName"]) + BX.UploaderUtils.sizeof(post.data[this.params["uploadInputInfoName"]]);
			}
			post.length = post.size + size;
			return post;
		},
		FormData : window.FormData,
		preparePackage : function(packageIndex, files, formData)
		{
			var fd = new this.FormData(), item, data = formData;
			for (item in data)
			{
				if (data.hasOwnProperty(item))
				{
					this.appendToForm(fd, item, data[item]);
				}
			}
			for (var id in files)
			{
				if (files.hasOwnProperty(id))
				{
					data = files[id];

					if (!!data.props)
					{
						data.props = this.prepareData(data.props);
						for (item in data.props)
						{
							if (data.props.hasOwnProperty(item))
							{
								this.appendToForm(fd, this.params["uploadInputName"] + '[' + id + '][' + item + ']', data.props[item]);
							}
						}
					}
					if (!!data.files)
					{
						for (var ii = 0; ii < data.files.length; ii++)
						{
							item = data.files[ii];
							files[id].files[ii].postName = item.postName =
								item.name + (!!item.thumb ? ('\\' + item.thumb + '\\') : (item.packages > 0 ? ('/' + item.packages + '/' + item.package + '/') : ''));
							fd.append((this.params["uploadInputName"] + '[' + id + '][' + this.prepareData(item.postName) + ']'), item, this.prepareData(item.postName));
						}
					}
				}
			}
			return fd;
		},
		sendPackage : function(stream, packageIndex, files, formData)
		{
			var fd = this.preparePackage(packageIndex, files, formData);
			this.onStartPackage(stream, packageIndex, files);
			this.send(stream, packageIndex, fd);
		},
		send : function(stream, packageIndex, fd)
		{
			this.xhr = BX.ajax({
				'method': 'POST',
				'dataType': 'json',
				'data' : fd,
				'url': this.uploadFileUrl,
				'onsuccess': BX.proxy(function(data){this.onDonePackage(stream, packageIndex, data);}, this),
				'onfailure': BX.proxy(function(){this.onErrorPackage(stream, packageIndex, arguments);}, this),
				'start': false,
				'preparePost':false,
				'processData':true
			});
			this.xhr.upload.addEventListener(
				'progress',
				BX.proxy(function(e){this.onProcessPackage(stream, packageIndex, e);}, this),
				false
			);
			this.xhr.send(fd);
		},
		submit : function(asynch)
		{
			this.onStart(asynch);
		},
		stop : function()
		{
			this.onTerminate();
		},
		onUpload : function(stream, item, status, params)
		{
			var text = '', packageIndex = item.pIndex, percent = 0;
			if (this.queue.itFailed.hasItem(item.id))
			{
				text = 'response [we do not work with errored]';
			}
			else if (status == statuses.error)
			{
				delete item.progress;
				this.queue.itFailed.setItem(item.id, item);
				this.queue.itForUpload.removeItem(item.id);

				BX.onCustomEvent(this, "onFileIsUploadedWithError", [item.id, item, params, this]);
				BX.onCustomEvent(item, "onUploadError", [item, params, this]);
				text = 'response [error]';
			}
			else if (status == statuses.uploaded)
			{
				delete item.progress;
				this.queue.itUploaded.setItem(item.id, item);
				this.queue.itForUpload.removeItem(item.id);

				BX.onCustomEvent(this, "onFileIsUploaded", [item.id, item, params, this]);
				BX.onCustomEvent(item, "onUploadDone", [item, params, this]);
				text = 'response [uploaded]';
			}
			else if (status == statuses.inprogress)
			{
				if (typeof params == "number")
				{
					if (params == 0 && item.progress.status == statuses["new"])
					{
						BX.onCustomEvent(item, "onUploadStart", [item, 0, this]);
						item.progress.status = statuses.inprogress;
					}

					percent = item.progress.uploaded + (item.progress.streams[stream.id] * params) / 100;
				}
				else
				{
					item.progress.uploaded += item.progress.streams[stream.id];
					item.progress.streams[stream.id] = 0;
					percent = item.progress.uploaded;
				}
				text = 'response [uploading]. Uploaded: ' + percent;
				BX.onCustomEvent(item, "onUploadProgress", [item, percent, this]);
			}
			else
			{
				if (status == statuses["new"])
				{
					var chunks = (item.getThumbs("getCount") > 0 ? item.getThumbs("getCount") : 0)
						+ 2;// props + (default canvas || file)

					item.progress = {
						percentPerChunk : (100 / chunks),
						streams : {},
						uploaded : 0,
						status : statuses["new"]
					};
					item.progress.streams[stream.id] = item.progress.percentPerChunk;
					text = 'request preparing [start]. Prepared: ' + item.progress.streams[stream.id];
				}
				else if (status == statuses.preparing)
				{
					item.progress.streams[stream.id] = (item.progress.streams[stream.id] > 0 ? item.progress.streams[stream.id] : 0) +
						(params.package / params.packages) * item.progress.percentPerChunk;

					text += 'request preparing [cont]. Prepared: ' + item.progress.streams[stream.id];
				}
				else
				{
					text = 'request preparing [finish]. ';
				}
				BX.onCustomEvent(item, "onUploadPrepared", [item, params, this]);
			}
			this.log(item.name + ': ' + text);
		},
		onTerminate : function(pIndex)
		{
			var packageFormer;
			if (!!pIndex && this.uploads.hasItem(pIndex))
			{
				if (this.upload.pIndex == pIndex)
					this.upload = null;
				this.log(pIndex + ' Uploading is canceled');
				packageFormer = this.uploads.removeItem(pIndex);
				this.queue.restoreFiles(packageFormer.dataId);
				BX.onCustomEvent(this, 'onTerminated', [pIndex]);
			}
			else if (!pIndex)
			{
				this.upload = null;
				while ((packageFormer = this.uploads.getFirst()) && !!packageFormer)
				{
					this.uploads.removeItem(packageFormer.pIndex);
					packageFormer.stop();
					this.log(packageFormer.pIndex + ' Uploading is canceled');
					this.queue.restoreFiles(packageFormer.dataId);
					BX.onCustomEvent(this, 'onTerminate', [pIndex, packageFormer]);
				}
			}
		},
		onStart : function()
		{
			this.uploads = (!!this.uploads ? this.uploads : new BX.UploaderUtils.Hash());
			this.upload = (!!this.upload ? this.upload : null);
			var pIndex = 'pIndex' + BX.UploaderUtils.getId(), queue = this.queue.itForUpload;
			this.queue.itForUpload = new BX.UploaderUtils.Hash();
			this.log(pIndex + ' Uploading is started');
			this.post = false;
			var packageFormer = new BX.UploaderPackage(queue, true);
			BX.onCustomEvent(this, 'onStart', [pIndex, packageFormer, this]);
			packageFormer.pIndex = pIndex;
			if (packageFormer.length > 0)
			{
				this.uploads.setItem(pIndex, packageFormer);
				this.checkUploads(pIndex);
			}
			else
				this.onDone(null, pIndex, null);
		},
		letForm : function(item, package)
		{
			if (!this.stream)
			{
				this.stream = this.streams.getFirst();
				if (!!this.stream)
				{
					this.streams.removeItem(this.stream.id);
					var post = this.preparePost( { packageIndex : package.pIndex, filesCount : package.filesCount, mode : "upload" }, true);
					this.stream.pIndex = package.pIndex;
					this.stream.post = post.data;
					this.stream.files = {};
					this.stream.postSize = post.length;
					this.stream.filesCount = 0;
				}
			}
			if (!!this.stream)
			{
				if (item === statuses.inprogress)
				{
					setTimeout(BX.proxy(function(){ this.checkUploads(package.pIndex); }, this), 500);
					return statuses.inprogress;
				}
				else if (item === statuses.done)
				{
				}
				else
				{
					var count = (this.limits["phpMaxFileUploads"] - this.stream.filesCount),
						size = (this.limits["phpPostMaxSize"] - this.stream.postSize),
						blob, file, data = {files : []}, tmp, error;

					while (!!item && size > 0 && count > 0)
					{
						if (item.uploadStatus != statuses.preparing)
						{
							error = this.checkFile(item);
							if (error === '')
							{
								item.uploadStatus = statuses.preparing;
								data.props = item.getProps();
								this.onUpload(this.stream, item, statuses["new"]);
								package.checkedFilesCount = (package.checkedFilesCount > 0 ? package.checkedFilesCount : 0) + 1;
							}
							else
							{
								this.onUpload(this.stream, item, statuses.error, {error : error});
								data.props = {name : item.name, error : true};
								item.uploadStatus = statuses.error;
								package.notCheckedFiles = (!!package.notCheckedFiles ? package.notCheckedFiles : []);
								package.notCheckedFiles.push(item.id);
							}
						}
						else
						{
							if (item.file.uploadStatus != statuses.done)
								file = item.file;
							else if (item.thumb !== null && !!item.thumb)
								file = item.thumb;
							else
								item.thumb = file = item.getThumbs(null);
							if (file === null)
							{
								item.uploadStatus = statuses.done;
								this.onUpload(this.stream, item, statuses.done);
								item.file.uploadStatus = statuses.done;
								item.thumb = null;
							}
							else
							{
								blob = BX.UploaderUtils.getFilePart(file, size, this.limits["phpPostMaxSize"]);
								if (!blob)
								{
									this.onUpload(this.stream, item, statuses.error, {error : BX.message('FILE_BAD_SIZE')});
									data.props = "error";
									item.uploadStatus = statuses.error;
								}
								else
								{
									data.files.push(blob);
									if (item.file == file && blob == file)
									{
										this.onUpload(this.stream, item, statuses.preparing, {canvas : "default", package : 1, packages : 1});
									}
									else if (item.file == file)
									{
										this.onUpload(this.stream, item, statuses.preparing, {canvas : "default", package : (blob.package + 1), packages : blob.packages, blob : blob});
									}
									else if (blob == file)
									{
										this.onUpload(this.stream, item, statuses.preparing, {canvas : item.thumb.thumb, package : 1, packages : 1, blob : blob});
										item.thumb = null;
									}
									else
									{
										this.onUpload(this.stream, item, statuses.preparing,
											{canvas : item.thumb.thumb, package : (blob.package + 1), packages : blob.packages, blob : blob});
										if (item.thumb.uploadStatus == statuses.done)
											item.thumb = null;
									}
								}
							}
						}
						if (data.files.length > 0 || !!data["props"])
						{
							tmp = BX.UploaderUtils.sizeof(data.files) + (!!data["props"] ? BX.UploaderUtils.sizeof(data.props) : 0);
							size -= tmp;
							if (size > 0)
							{
								this.stream.postSize += tmp;
								this.stream.files[item.id] = BX.UploaderUtils.makeAnArray(this.stream.files[item.id], data);

								if (data.files.length) { count--; this.stream.filesCount++;}
							}
							data = {files : []};
						}
						if (item.uploadStatus !== statuses.preparing)
						{
							break;
						}
					}
					if (!!item && size > 0 && count > 0)
					{
						return ((item.uploadStatus !== statuses.preparing) ? statuses.done : statuses.inprogress);
					}
				}
				if (BX.util._array_keys_ob(this.stream.files).length > 0)
				{
					if (package.sended !== true && !!package.notCheckedFiles && package.notCheckedFiles.length > 0)
					{
						package.sended = true;
						if (package.notCheckedFiles.length >= package.filesCount)
						{
							this.onDone(this.stream, package.pIndex, null);
							return statuses.done;
						}
						else
						{
							var jy;
							for (var jt = 0; jt < package.notCheckedFiles.length; jt++)
							{
								jy = package.notCheckedFiles[jt];
								package.filesCount--;
								delete this.stream.files[jy];
							}
							post = this.preparePost( { packageIndex : package.pIndex, filesCount : package.filesCount, mode : "upload" }, true);
							this.stream.post = post.data;
						}
					}
					this.sendPackage(this.stream, package.pIndex, this.stream.files, this.stream.post);
					this.stream = null;
					if (this.streams.length > 0)
					{
						return ((item.uploadStatus !== statuses.preparing) ? statuses.done : statuses.inprogress);
					}
				}
				else
				{
					this.streams.setItem(this.stream.id, {id : this.stream.id});
					this.stream = null;
				}
				return statuses.done;
			}
			return statuses.inprogress;
		},
		checkUploads : function(pIndex)
		{
			if (this.upload === null)
			{
				this.upload = this.uploads.getFirst();
			}
			if (!!this.upload && this.upload.pIndex == pIndex)
			{
				if (!this.upload.uNumber)
					this.upload.uNumber = 0;
				else
					this.upload.uNumber++;
				this.onContinue(this.upload.pIndex, this.upload.uNumber );

				this._letForm = (!!this._letForm ? this._letForm : BX.delegate(this.letForm, this));
				this.upload.packFiles(this._letForm);
				return true;
			}
			return false;
		},
		onContinue : function(pIndex)
		{
			this.log(pIndex + ' Uploading is continued');
			BX.onCustomEvent(this, 'onContinue', [pIndex]);
		},
		onDone : function(stream, pIndex, files)
		{
			var res = this.uploads.removeItem(pIndex);
			this.log(pIndex + ' Uploading is done');
			if (!!res)
			{
				this.upload = null;
				BX.onCustomEvent(this, 'onDone', [stream, pIndex, res, files]);
				BX.defer_proxy(this.checkUploads, this)();
			}
			else
			{
				BX.onCustomEvent(this, 'onDone', [stream, pIndex, { pIndex : pIndex, postSize : 0, filesCount : 0 }, files]);
				BX.defer_proxy(this.checkUploads, this)();
			}
		},
		onError : function(pIndex, data)
		{
			this.log(pIndex + ' Uploading is failed');
			data = (!!data && data[1] && data[1]["data"] ? data[1]["data"] : BX.message('UPLOADER_UPLOADING_ERROR'));
			alert(data);
			BX.onCustomEvent(this, 'error', [data]);
			BX.onCustomEvent(this, 'onError', [data]);
		},
		onStartPackage : function(stream, pIndex, data)
		{
			this.log(pIndex + ' package is started');
			if (!!this.upload)
			{
				var item, id;
				for (id in stream.files)
				{
					if (stream.files.hasOwnProperty(id))
					{
						item = this.upload.data.getItem(id);
						if (!!item)
						{
							this.onUpload(stream, item, statuses.inprogress, 0);
						}
					}
				}
			}
			BX.onCustomEvent(this, 'startPackage', [stream, pIndex, data]);
		},
		onProcessPackage : function(stream, pIndex, e) {
			var procent = 15;
			if(e.lengthComputable) {
				procent = e.loaded * 100 / (e.total || e.totalSize);
			}
			else if (e > procent)
				procent = e;
			procent = (procent > 5 ? procent : 5);

			if (!!this.upload)
			{
				var item, id;
				for (id in stream.files)
				{
					if (stream.files.hasOwnProperty(id))
					{
						item = this.upload.data.getItem(id);
						if (!!item)
						{
							this.onUpload(stream, item, statuses.inprogress, procent);
						}
					}
				}
			}
			BX.onCustomEvent(this, 'processPackage', [stream, pIndex, procent]);
			return procent;
		},
		onDonePackage : function(stream, pIndex, data)
		{
			this.log(pIndex + ' package is done');
			this.streams.setItem(stream.id, {id : stream.id});
			if (!!data && typeof data == "object")
			{
				var item, id, file, ii;
				for (id in stream.files)
				{

					item = this.queue.items.getItem(id);
					if (stream.files.hasOwnProperty(id) && !!item)
					{
						file = data.files[id];
						item.hash = file.hash;
						if (file.status == "error")
						{
							this.onUpload(stream, item, statuses.error, file);
						}
						else if (file.status == "uploaded")
						{
							this.onUpload(stream, item, statuses.uploaded, file);
						}
						else // chunks
						{
							this.onUpload(stream, item, statuses.inprogress, file);
						}
					}
				}
				BX.onCustomEvent(this, 'donePackage', [stream, pIndex, data]);
				if (data["status"] == "done")
				{
					this.onDone(stream, pIndex, data);
				}
				else
				{
					BX.defer_proxy(function(){ this.checkUploads(pIndex); }, this)();
				}
			}
			else
			{
				this.onErrorPackage(stream, pIndex, data);
			}
		},
		onErrorPackage : function(stream, packageIndex, data)
		{
			stream = !!stream ? stream : {};
			stream["try"] = (!!stream["try"] && stream["try"] > 0 ? stream["try"] : 0);
			stream["try"]++;
			if (stream["try"] > 2)
			{
				this.onError(data);
				this.onTerminate(packageIndex);
				this.onTerminate();
			}
			else
			{
				this.sendPackage(stream, packageIndex, stream.files, stream.post);
			}
		}
	};

	BX.UploaderSimple = function(params)
	{
		BX.UploaderSimple.superclass.constructor.apply(this, arguments);
		this.dialogName = "BX.UploaderSimple";
		BX.addCustomEvent(this, "onFileNeedsPreview", BX.delegate(function(id, item) {
			this.previews = (!!this.previews ? this.previews : new BX.UploaderUtils.Hash());
			this.previews.setItem(item.id, item);
			this.previewsQueue = (!!this.previewsQueue ? this.previewsQueue : new BX.UploaderUtils.Hash());
			setTimeout(BX.delegate(this.onFileNeedsPreview, this), 500);
		}, this));
		this.streams = new BX.UploaderUtils.Hash();
		this.streams.setItem('stream0', {id : 'stream0'});
		return this;
	};
	BX.extend(BX.UploaderSimple, BX.Uploader);

	BX.UploaderSimple.prototype.onFileNeedsPreviewCallback = function(packageIndex, data)
	{
		this.log('onFileNeedsPreviewCallback');
		var queue = this.previewsQueue.removeItem(packageIndex);
		this.onFileNeedsPreview();
		data = (typeof data == "object" && !!data ? data : {});
		data["files"] = (!!data["files"] ? data["files"] : {});
		if (!!queue)
		{
			var item, file, checked = false;
			while((item = queue.getFirst()) && !!item)
			{
				queue.removeItem(item.id);
				checked = false;
				try
				{
					if (!!data["files"][item.id])
					{
						if (data["files"][item.id]["status"] == "uploaded" && !!data["files"][item.id]["hash"])
						{
							file = data.files[item.id]["file"]["files"]["default"];
							item.file = {
								hash : data["files"][item.id]["hash"],
								copy : "default",
								id : item.id,
								"name" : file["name"],
								"~name" : file["~name"],
								size : parseInt(file["size"]),
								type : file["type"],
								url : file["url"]
							};
							checked = true;
							BX.onCustomEvent(item, "onFileHasGotPreview", [item.id, item]);
							continue;
						}
					}
				}
				catch(e) {
					checked = null
				}
				BX.onCustomEvent(item, "onFileHasNotGotPreview", [item.id, item]);
				if (checked === null)
					this.onUpload(null, item, statuses.error, {error : BX.message('UPLOADER_UPLOADING_ERROR')});
				else
					this.onUpload(null, item, statuses.error, {error : data["files"][item.id]["error"]});
			}
		}
	};
	BX.UploaderSimple.prototype.onFileNeedsPreview = function()
	{
		var packageIndex = 'preview' + BX.UploaderUtils.getId(),
			post = this.preparePost({packageIndex : packageIndex, filesCount : this.limits["phpMaxFileUploads"], mode : "upload", type : "brief"}, true),
			count = this.limits["phpMaxFileUploads"],
			item, files = false, items = new BX.UploaderUtils.Hash();

		while (count > 0 && this.previews.length > 0 && (item = this.previews.getFirst()) && !!item && item !== null)
		{
			this.previews.removeItem(item.id);
			files = (files === false ? {} : files);
			files[item.id] = {files : [item.file], props : {name : item.name}};
			count--;
			items.setItem(item.id, item);
		}
		if (files !== false)
		{
			post = this.preparePost({packageIndex : packageIndex, filesCount : (this.limits["phpMaxFileUploads"]-count), mode : "upload", type : "brief"}, true);
			this.previewsQueue.setItem(packageIndex, items);
			var fd = this.preparePackage(packageIndex, files, post.data, (this.limits["phpMaxFileUploads"] - count));
			this.send(null, packageIndex, fd, BX.proxy(function(data) { this.onFileNeedsPreviewCallback(packageIndex, data); }, this));
		}
	};
	BX.UploaderSimple.prototype.init = function(fileInput, del)
	{
		this.log('Initialized: ' + (del !== false ? 'drop' : ' does not drop'));
		if (fileInput == this.fileInput)
			this.fileInput = fileInput = this.mkFileInput(fileInput, del);
		else
			fileInput = this.mkFileInput(fileInput, del);
		if (fileInput)
		{
			BX.bind(fileInput, "change", BX.delegate(this.onChange, this));
			return true;
		}
		return false;
	};
	BX.UploaderSimple.prototype.log = function(text) { BX.UploaderUtils.log('simpleup', text); };
	BX.UploaderSimple.prototype.mkFileInput = function(oldNode, del)
	{
		if (!BX(oldNode))
			return false;
		BX.unbindAll(oldNode);
		var node = oldNode.cloneNode(true);
		BX.adjust(node, {
			attrs: {
				id : "",
				name: (this.params["uploadInputName"] + '[file' + BX.UploaderUtils.getId() + ']'),
				multiple : false,
				accept : this.limits["uploadFile"]
		}});
		oldNode.parentNode.insertBefore(node, oldNode);
		if (del !== false)
			oldNode.parentNode.removeChild(oldNode);

		return node;
	};
	BX.UploaderSimple.prototype.onChange = function(fileInput)
	{
		fileInput = (fileInput.target || fileInput.srcElement || this.fileInput);

		BX.PreventDefault(fileInput);
		if (!!(fileInput.value))
		{
			if (this.params["doWeHaveStorage"])
				this.init(fileInput, false);
			else
				this.queue.clear();
			var ext = (fileInput.value.name || '').split('.').pop(), err = false;

			if (this.limits["uploadFileExt"].length > 0)
			{
				err = (this.limits["uploadFileExt"].indexOf(ext) < 0);
			}
			else if (this.limits["uploadFile"] == "image/*")
			{
				err = (this.params["imageExt"].indexOf(ext) < 0);
			}
			if (!err)
			{
				if (this.params["imageExt"].indexOf(ext) >= 0)
					fileInput.fileType = "image/xyz";
				BX.onCustomEvent(this, "onItemIsAdded", [fileInput, this]);
				BX.onCustomEvent(this, "onItemsAreAdded", [this]);

				if (this.asynch)
					this.submit(true);
			}
		}
		return false;
	};
	BX.UploaderSimple.prototype.appendToForm = function(fd, key, val)
	{
		if (!!val && typeof val == "object")
		{
			for (var ii in val)
			{
				if (val.hasOwnProperty(ii))
				{
					this.appendToForm(fd, key + '[' + ii + ']', val[ii]);
				}
			}
		}
		else
			fd.append(key, (!!val ? val : ''));
	};
	BX.UploaderSimple.prototype.FormData = function()
	{
		var uniqueID;
		do {
			uniqueID = Math.floor(Math.random() * 99999);
		} while(BX("form-" + uniqueID));

		this.form = BX.create("FORM", {
			props: {
				id: "form-" + uniqueID,
				method: "POST",
				enctype: "multipart/form-data",
				encoding: "multipart/form-data"
			},
			style: {display: "none"}
		});
		document.body.appendChild(this.form);
	};
	BX.UploaderSimple.prototype.FormData.prototype = {
		append : function(name, val)
		{
			if (BX.type.isDomNode(val))
			{
				this.form.appendChild(val);
			}
			else
			{
				this.form.appendChild(
					BX.create(
						"INPUT",
						{
							props : {
								type : "hidden",
								name : name,
								value : val
							}
						}
					)
				)
			}
		}
	}
	BX.UploaderSimple.prototype.send = function(stream, packageIndex, fd, callback)
	{
		if(!this.onBeforeUnload)
			this.onBeforeUnload = BX.delegate(this.beforeunload, this);
		BX.bind(window, 'beforeunload', this.onBeforeUnload);

		BX.adjust(fd.form, { props: { action: this.uploadFileUrl } } );
		BX.ajax.submit(fd.form, BX.proxy(function(innerHTML){this.aftersubmit(stream, packageIndex, innerHTML, callback)}, this));
		if (!callback)
			this.onProcessPackage(stream, packageIndex, 90);
	};

	BX.UploaderSimple.prototype.aftersubmit = function(stream,packageIndex, data, callback)
	{
		BX.unbind(window, 'beforeunload',this.onBeforeUnload);
		var res = BX.parseJSON(BX.util.htmlspecialcharsback(data.replace(/^<(.*?)>(.*?)<(.*?)>$/gi, "$2")), {});
		if (!!callback)
		{
			callback(res);
		}
		else if (!!res)
		{
			this.onDonePackage(stream, packageIndex, res);
		}
		else
		{
			this.onErrorPackage(stream, packageIndex, data);
		}
	};
	BX.UploaderSimple.prototype.beforeunload = function(e)
	{
		this.stop();
	};
	BX.Uploader.getInstance = function(params)
	{
		if (window.Blob || window.MozBlobBuilder || window.WebKitBlobBuilder || window.BlobBuilder)
			return new BX.Uploader(params);
		return new BX.UploaderSimple(params);
	}

	BX.UploaderPackage = function(raw)
	{
		this.filesCount = 0;
		this.length = 0;
		if (!!raw && raw.length > 0)
		{
			/**
			 * this.length integer
			 * this.raw BX.UploaderUtils.Hash()
			 * this.data BX.UploaderUtils.Hash()
			 */
			this.length = raw.length;
			this.filesCount = raw.length;
			this.raw = raw;
			this.dataId = raw.order.join(",").split(",");
			this.data = new BX.UploaderUtils.Hash();
			this.init();
		}
	};

	BX.UploaderPackage.prototype = {
		stop: function()
		{
			this.status = statuses.terminate;
		},
		log : function(text)
		{
			BX.UploaderUtils.log('package', text);
		},
		init : function()
		{
			var item, callback = BX.proxy(function(id, item) {
				if (this.raw.removeItem(id))
				{
					this.data.setItem(id, item);
					this.init();
				}
			} , this);

			while ((item = this.raw.getFirst()) && !!item)
			{
				if (item.status === statuses["new"])
				{
					BX.addCustomEvent(item, "onFileIsInited", callback);
					break;
				}
				else
				{
					callback(item.id, item);
				}
			}
		},
		packFiles : function(callback)
		{
			if (!this.callback)
				this.callback = callback;
			var item, res;
			while ((item = this.data.getNext()) && !!item)
			{
				if ((res = callback(item, this)) && res != statuses.done)
				{
					this.data.pointer--;
					return statuses.inprogress;
				}
			}
			if (!item && this.data.length < this.filesCount)
			{
				callback(statuses.inprogress, this);
				return statuses.inprogress;
			}
			callback(statuses.done, this);
			return statuses.done;
		}
	};
}(window));