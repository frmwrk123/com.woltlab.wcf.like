/**
 * Like support for WCF
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Like = function() { this.init(); }
WCF.Like.prototype = {
	/**
	 * list of likeable objects
	 * 
	 * @var	jQuery
	 */
	_containers: null,
	
	/**
	 * proxy object
	 * 
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes like support.
	 */
	init: function() {
		this._containers = $('.likeButtonContainer');
		
		var options = {
			success: $.proxy(this._success, this)
		}
		this._proxy = new WCF.Action.Proxy(options);
		
		this._containers.each($.proxy(function(index, container) {
			$(container).find('span').bind('click', $.proxy(this._click, this));
		}, this));
	},
	
	/**
	 * Handles likes and dislikes.
	 * 
	 * @param	object		event
	 */
	_click: function(event) {
		var $target = $(event.target);
		var $container = $target.parent();
		
		var $action = $target.data('action');
		var $isDislike = ($target.data('type') == 'dislike') ? 1 : 0;
		var $objectType = $container.data('objectType');
		var $objectID = $container.data('objectID');
		
		//this._proxy.showSpinner($target);
		this._sendRequest($objectID, $action, $objectType, $isDislike);
	},
	
	/**
	 * Sends request through proxy.
	 * 
	 * @param	integer		objectID
	 * @param	string		action
	 * @param	string		objectType
	 * @param	boolean		isDislike
	 */
	_sendRequest: function(objectID, action, objectType, isDislike) {
		this._proxy.setOption('data', {
			actionName: action,
			className: 'wcf\\data\\like\\LikeAction',
			parameters: {
				data: {
					isDislike: isDislike,
					objectType: objectType
				}
			},
			objectIDs: [ objectID ]
		});
		
		this._proxy.sendRequest();
	},
	
	/**
	 * Updates likeable object.
	 * 
	 * @param	object		data
	 * @param	string		textStatus
	 * @param	object		jqXHR
	 */
	_success: function(data, textStatus, jqXHR) {
		// update items
		this._containers.each(function(index, container) {
			var $container = $(container);
			if (WCF.inArray($container.data('objectID'), data.objectIDs)) {
				var $buttonPlus = $container.children('span.likeButtonPlus');
				var $buttonMinus = $container.children('span.likeButtonMinus');
				
				// update stats
				$buttonPlus.html(data.returnValues.likes).removeClass('active');
				$buttonMinus.html(data.returnValues.dislikes).removeClass('active');
				
				// mark buttons as active
				if (data.returnValues.isLiked) {
					$buttonPlus.addClass('active');
				}
				else if (data.returnValues.isDisliked) {
					$buttonMinus.addClass('active');
				}
			}
		});
	}
}