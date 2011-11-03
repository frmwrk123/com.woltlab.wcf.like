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
				'likeButton': null,
				'badge': null,
				'dislikeButton': null,
				'cumulativeLikes': $container.data('like-cumulativeLikes'),
				'objectType': $container.data('objectType'),
				'objectID': this._getObjectID($containerID),
				'users': eval($container.data('like-users'))
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
	 * Adds buttons for like and dislike.
	 * 
	 * @param	integer		containerID
	 * @param	jQuery		likeButton
	 * @param	jQuery		cumulativeLikes
	 * @param	jQuery		dislikeButton
	 */
	_addButtons: function(containerID, likeButton, cumulativeLikes, dislikeButton) {
		var $buttonContainer = this._getButtonContainer(containerID);
		
		likeButton.appendTo($buttonContainer);
		cumulativeLikes.appendTo($buttonContainer);
		dislikeButton.appendTo($buttonContainer);
	},
	
	/**
	 * Creates buttons for like and dislike.
	 * 
	 * @param	integer		containerID
	 */
	_createButtons: function(containerID) {
		var $likeButton = $('<li class="likeButton balloonTooltip" title="'+WCF.Language.get('wcf.like.button.like')+'"><a><img src="' + WCF.Icon.get('wcf.icon.like') + '" alt="" /></a></li>');
		var $cumulativeLikes = $('<li class="likeButton balloonTooltip"><a><span class="badge"></span></a></li>').data('containerID', containerID);
		var $dislikeButton = $('<li class="likeButton balloonTooltip" title="'+WCF.Language.get('wcf.like.button.dislike')+'"><a><img src="' + WCF.Icon.get('wcf.icon.dislike') + '" alt="" /></a></li>');
		this._addButtons(containerID, $likeButton, $cumulativeLikes, $dislikeButton);
				
		this._containerData[containerID].likeButton = $likeButton;
		this._containerData[containerID].badge = $cumulativeLikes;
		this._containerData[containerID].dislikeButton = $dislikeButton;

		$likeButton.data('containerID', containerID).data('type', 'like').click($.proxy(this._click, this));
		$dislikeButton.data('containerID', containerID).data('type', 'dislike').click($.proxy(this._click, this));
		
		this._updateBadge(containerID);
	},
	
	/**
	 * Handles likes and dislikes.
	 * 
	 * @param	object		event
	 */
	_click: function(event) {
		var $button = $(event.currentTarget);
		if ($button === null) {
			console.debug("[WCF.Like] Unable to find target button, aborting.");
			return;
		}

		this._sendRequest($button.data('containerID'), $button.data('type'));
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

		// update label
		this._updateBadge($containerID);
		//this._containerData[$containerID].badge.find('span').text((data.returnValues.cumulativeLikes > 0 ? '+' : '') + data.returnValues.cumulativeLikes);
		
		// mark button as active
		var $likeButton = this._containerData[$containerID].likeButton.removeClass('active');
		var $dislikeButton = this._containerData[$containerID].dislikeButton.removeClass('active');

		if (data.returnValues.isLiked) {
			$likeButton.addClass('active');
		}
		else if (data.returnValues.isDisliked) {
			$dislikeButton.addClass('active');
		}
	},
	
	_updateBadge: function(containerID) {
		var $users = this._containerData[containerID].users;
		var $userArray = [];
		for (var $userID in $users) $userArray.push($users[$userID]);
		var $usersString = $userArray.join(', ');
		
		if ($usersString) this._containerData[containerID].badge.attr('title', $usersString + ' gefaellt das.');
		else this._containerData[containerID].badge.attr('title', '');
		this._containerData[containerID].badge.find('.badge').text((this._containerData[containerID].cumulativeLikes > 0 ? '+' : '') +  this._containerData[containerID].cumulativeLikes);
	}
});
