/**
 * Like support for WCF
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Like = Class.extend({
	/**
	 * list of containers
	 * @var	object
	 */
	_containers: { },

	/**
	 * container meta data
	 * @var	object
	 */
	_containerData: { },

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
		var $containers = this._getContainers();
		if ($containers.length === 0) {
			console.debug("[WCF.Like] Empty container set given, aborting.");
			return;
		}

		$containers.each($.proxy(function(index, container) {
			// set container
			var $container = $(container);
			var $containerID = $container.wcfIdentify();
			this._containers[$containerID] = $container;

			// set container data
			this._containerData[$containerID] = {
				'buttonContainer': null,
				'cumulativeLikes': $container.data('like-cumulativeLikes'),
				'objectType': $container.data('objectType'),
				'objectID': this._getObjectID($containerID),
				'users': $container.data('like-users')
			};

			// create UI
			this._createButtons($containerID);
		}, this));

		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
	},

	/**
	 * Returns a list of available object containers.
	 * 
	 * @return	jQuery
	 */
	_getContainers: function() { },

	/**
	 * Returns button container for target object container.
	 * 
	 * @param	string		containerID
	 * @return	jQuery
	 */
	_getButtonContainer: function(containerID) { },

	/**
	 * Returns object id for targer object container.
	 * 
	 * @param	string		containerID
	 * @return	integer
	 */
	_getObjectID: function(containerID) { },

	/**
	 * Creates buttons for like and dislike.
	 * 
	 * @param	integer		containerID
	 */
	_createButtons: function(containerID) {
		var $buttonContainer = this._getButtonContainer(containerID);
		var $listItem = $('<li class="likeButtons"></li>').data('containerID', containerID).appendTo($buttonContainer);
		this._containerData[containerID].buttonContainer = $listItem;

		var $buttonLike = $('<img src="' + WCF.Icon.get('wcf.icon.like') + '" alt="" />').appendTo($listItem);
		var $cumulativeLikes = $('<span>' + this._containers[containerID].data('like-cumulativeLikes') + '</span>').appendTo($listItem);
		var $buttonDislike = $('<img src="' + WCF.Icon.get('wcf.icon.dislike') + '" alt="" />').appendTo($listItem);

		$buttonLike.data('type', 'like').click($.proxy(this._click, this));
		$buttonDislike.data('type', 'dislike').click($.proxy(this._click, this));

		return $listItem;
	},
	
	/**
	 * Handles likes and dislikes.
	 * 
	 * @param	object		event
	 */
	_click: function(event) {
		var $button = $(event.target);
		var $buttonContainer = $button.parent();
		
		this._sendRequest($buttonContainer.data('containerID'), $button.data('type'));
	},
	
	/**
	 * Sends request through proxy.
	 * 
	 * @param	integer		containerID
	 * @param	string		type
	 */
	_sendRequest: function(containerID, type) {
		this._proxy.setOption('data', {
			actionName: type,
			className: 'wcf\\data\\like\\LikeAction',
			parameters: {
				data: {
					containerID: containerID,
					objectID: this._containerData[containerID].objectID,
					objectType: this._containerData[containerID].objectType
				}
			}
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
		var $containerID = data.returnValues.containerID;
		if (!this._containers[$containerID]) {
			return;
		}
		
		// update container data
		this._containerData[$containerID].cumulativeLikes = data.returnValues.cumulativeLikes;
		this._containerData[$containerID].users = data.returnValues.users;

		console.debug(data.returnValues.users);

		// update label
		var $buttonContainer = this._containerData[$containerID].buttonContainer;
		$buttonContainer.children('span').first().text(data.returnValues.cumulativeLikes);
		
		// mark button as active
		var $buttons = $buttonContainer.children('img');
		var $buttonLike = $buttons.first().removeClass('active');
		var $buttonDislike = $buttons.last().removeClass('active');

		if (data.returnValues.isLiked) {
			$buttonLike.addClass('active');
		}
		else if (data.returnValues.isDisliked) {
			$buttonDislike.addClass('active');
		}
	}
});
