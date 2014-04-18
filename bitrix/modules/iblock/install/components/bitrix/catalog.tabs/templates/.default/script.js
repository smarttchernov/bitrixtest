JCCatalogTabs = function (params)
{
	this.activeTabId = params.activeTabId;
	this.tabsContId = params.tabsContId;
};

JCCatalogTabs.prototype.onTabClick = function(tabObj)
{
	if(!tabObj || !tabObj.id || this.activeTabId == tabObj.id)
		return;

	this.setTabActive(tabObj);
};

JCCatalogTabs.prototype.setTabActive = function(tabObj)
{
	if(!tabObj || !tabObj.id)
		return;

	var newActiveContent = BX(tabObj.id+"_cont");

	if(newActiveContent)
	{
		BX.addClass(tabObj, "active");
		BX.removeClass(newActiveContent, "tab-off");
		BX.removeClass(newActiveContent, "hidden");

		if(this.activeTabId != tabObj.id)
		{
			var oldActiveTab = BX(this.activeTabId);
			var oldActiveContent = BX(this.activeTabId+"_cont");

			if(oldActiveTab && oldActiveContent)
			{
				BX.removeClass(oldActiveTab, "active");
				BX.addClass(oldActiveContent, "tab-off");
				setTimeout(function() { BX.addClass(oldActiveContent, "hidden"); }, 700);

				this.activeTabId = tabObj.id;
				var tabId = tabObj.id.replace(this.tabsContId, "");
				BX.onCustomEvent('onAfterBXCatTabsSetActive_'+this.tabsContId,[{activeTab: tabId}]);
			}
		}
	}
};