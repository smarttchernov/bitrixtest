;(function(window) {
	if (window.JCCatalogSocnetsComments)
		return;

	JCCatalogSocnetsComments = {

		lastWidth: null,
		ajaxUrl: null,

		setFBWidth: function(width)
		{
			if(JCCatalogSocnetsComments.lastWidth == width)
				return;

			JCCatalogSocnetsComments.lastWidth = width;

			var fbDiv = BX("bx-cat-soc-comments-fb");

			if(fbDiv)
			{
				if(fbDiv.childNodes[0])
					fbDiv = fbDiv.childNodes[0];

				if(fbDiv && fbDiv.childNodes[0] && fbDiv.childNodes[0].childNodes[0])
				{
					var fbIframe = fbDiv.childNodes[0].childNodes[0];

					if(fbIframe)
					{
						var src = fbIframe.getAttribute("src");
						var newSrc = src.replace(/width=(\d+)/ig, "width="+width);

						fbDiv.setAttribute("data-width", width+"px");
						fbDiv.childNodes[0].style.width = width+"px";
						fbIframe.style.width = width+"px";

						fbIframe.setAttribute("src", newSrc);
					}
				}
			}
		},

		onFBResize: function(event)
		{
			var width = JCCatalogSocnetsComments.getWidth();

			if(width > 20)
				JCCatalogSocnetsComments.setFBWidth(width-20);
		},

		getWidth: function()
		{
			var result = 0,
				obj = BX("soc_comments_div");

			if(obj && obj.parentNode && obj.parentNode.parentNode)
			{
				var pos = BX.pos(obj.parentNode.parentNode);
				result = pos.width;
			}

			return result;
		},

		getBlogAjaxHtml: function()
		{
			var postData = {
					sessid: BX.bitrix_sessid()
				};

			BX.ajax({
				timeout:   30,
				method:   'POST',
				dataType: 'html',
				url:       JCCatalogSocnetsComments.ajaxUrl,
				data:      postData,
				onsuccess: function(result)
				{
					if(result)
					{
						JCCatalogSocnetsComments.insertBlogHtml(result);
						JCCatalogSocnetsComments.hacksForCommentsWindow(false);
					}
				}
			});
		},

		insertBlogHtml: function(html)
		{
			JCCatalogSocnetsComments.blogContainerObj = BX("bx-cat-soc-comments-blg");

			if(JCCatalogSocnetsComments.blogContainerObj)
				JCCatalogSocnetsComments.blogContainerObj.innerHTML = html;
		},

        hacksForCommentsWindow: function(addTitleShow)
        {
            window["iblockCatalogCommentsHIntervalId"] = setInterval( function(){
                if(typeof  showComment == 'function')
                {
                    if(!addTitleShow)
                        showComment("0");

                    var addCommentButtons = BX.findChildren(document,
                        { class: "blog-add-comment" }
                        , true
                    );

                    if(addCommentButtons[0])
                        for (var i = addCommentButtons.length-1; i >= 0 ; i--)
                            addCommentButtons[i].style.display = addTitleShow ? "" : "none";

                    clearInterval(window["iblockCatalogCommentsHIntervalId"]);
                }

            },
            200
            );
        }
    };
})(window);
