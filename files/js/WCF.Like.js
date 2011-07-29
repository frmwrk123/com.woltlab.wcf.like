WCF.Like = function() { this.init(); }
WCF.Like.prototype = {
	_objects: null,
	
	_proxy: null,
	
	init: function() {
		this._objects = $('.likeButton');
		
		var options = {
			success: $.proxy(this._success, this)
		}
		this._proxy = new WCF.Action.Proxy(options);
		
		this._objects.each($.proxy(function(index, object) {
			$(object).bind('click', $.proxy(this._click, this));
		}, this));
	},
	
	_click: function(event) {
		var $target = $(event.target);
		var $action = 'like';
		var $type = $target.data('type');
		
		if ($target.data('like') != 'undefined' && $target.data('like') == 1) {
			$action = 'unlike';
		}
		
		//this._proxy.showSpinner($target);
		this._sendRequest($target, $action, $type);
	},
	
	_sendRequest: function(object, action, type) {
		this._proxy.setOption('data', {
			actionName: action,
			className: 'wcf\\data\\like\\LikeAction',
			parameters: { objectType: type },
			objectIDs: [ $(object).data('objectID') ]
		});
		
		this._proxy.sendRequest();
	},
	
	_success: function(data, textStatus, jqXHR) {
		// update items
		this._objects.each(function(index, object) {
			var $objectID = $(object).data('objectID');
			if (WCF.inArray($objectID, data.objectIDs)) {
				// TODO
			}
		});
	}
}