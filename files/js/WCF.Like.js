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
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * user can like 
	 * @var boolean
	 */
	_canLike: false,
	
	/**
	 * Initializes like support.
	 */
	init: function(canLike) {
		this._canLike = canLike;
		var $containers = this._getContainers();
		this._initContainers($containers);
		
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		// bind dom node inserted listener
		WCF.DOMNodeInsertedHandler.addCallback('WCF.Like', $.proxy(this._domNodeInserted, this));
	},
	
	/**
	 * Initialize containers once new nodes are inserted.
	 */
	_domNodeInserted: function() {
		var $containers = this._getContainers();
		this._initContainers($containers);
	},
	
	/**
	 * Initializes like containers.
	 * 
	 * @param	object		containers
	 */
	_initContainers: function(containers) {
		containers.each($.proxy(function(index, container) {
			// set container
			var $container = $(container);
			var $containerID = $container.wcfIdentify();
			
			if (!this._containers[$containerID]) {
				this._containers[$containerID] = $container;
				
				// set container data
				this._containerData[$containerID] = {
					'likeButton': null,
					'badge': null,
					'dislikeButton': null,
					'cumulativeLikes': $container.data('like-cumulativeLikes'),
					'objectType': $container.data('objectType'),
					'objectID': this._getObjectID($containerID),
					'users': eval($container.data('like-users')),
					'liked': $container.data('like-liked')
				};
				
				// create UI
				this._createWidget($containerID);
			}
		}, this));
	},

	/**
	 * Returns a list of available object containers.
	 * 
	 * @return	jQuery
	 */
	_getContainers: function() { },

	/**
	 * Returns widget container for target object container.
	 * 
	 * @param	string		containerID
	 * @return	jQuery
	 */
	_getWidgetContainer: function(containerID) { },

	/**
	 * Returns object id for targer object container.
	 * 
	 * @param	string		containerID
	 * @return	integer
	 */
	_getObjectID: function(containerID) { },

	/**
	 * Adds the like widget.
	 * 
	 * @param	integer		containerID
	 * @param	jQuery		widget
	 */
	_addWidget: function(containerID, widget) {
		var $widgetContainer = this._getWidgetContainer(containerID);
		
		widget.appendTo($widgetContainer);
	},
	
	/**
	 * Builds the like widget.
	 * 
	 * @param	integer		containerID
	 * @param	jQuery		likeButton
	 * @param	jQuery		dislikeButton
	 * @param	jQuery		cumulativeLikes
	 */
	_buildWidget: function(containerID, likeButton, dislikeButton, cumulativeLikes) {
		var $widget = $('<aside class="wcf-likesWidget"><ul></ul></aside>');
		if (this._canLike) {
			dislikeButton.appendTo($widget.find('ul'));
			likeButton.appendTo($widget.find('ul'));
		}
		cumulativeLikes.appendTo($widget);
		
		this._addWidget(containerID, $widget); 
	},
	
	/**
	 * Creates the like widget.
	 * 
	 * @param	integer		containerID
	 */
	_createWidget: function(containerID) {
		var $likeButton = $('<li class="wcf-likeButton"><a title="'+WCF.Language.get('wcf.like.button.like')+'" class="jsTooltip"><img src="' + WCF.Icon.get('wcf.icon.like') + '" alt="" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.like')+'</span></a></li>');
		var $dislikeButton = $('<li class="wcf-dislikeButton"><a title="'+WCF.Language.get('wcf.like.button.dislike')+'" class="jsTooltip"><img src="' + WCF.Icon.get('wcf.icon.dislike') + '" alt="" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.dislike')+'</span></a></li>');
		var $cumulativeLikes = $('<p class="wcf-likesDisplay"><img src="' + WCF.Icon.get('wcf.icon.like') + '" alt="" /> <span class="wcf-likesText wcf-badge jsTooltip"></span></p>').data('containerID', containerID);
		this._buildWidget(containerID, $likeButton, $dislikeButton, $cumulativeLikes);
		
		this._containerData[containerID].likeButton = $likeButton;
		this._containerData[containerID].badge = $cumulativeLikes;
		this._containerData[containerID].dislikeButton = $dislikeButton;

		$likeButton.data('containerID', containerID).data('type', 'like').click($.proxy(this._click, this));
		$dislikeButton.data('containerID', containerID).data('type', 'dislike').click($.proxy(this._click, this));
		if (this._containerData[containerID].liked == 1) {
			$likeButton.addClass('active');
			$likeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.like.active'));
		}
		if (this._containerData[containerID].liked == -1) {
			$dislikeButton.addClass('active');
			$dislikeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.dislike.active'));
		}
		
		$cumulativeLikes.find('a').click(function() { alert('todo'); });
		
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
		this._containerData[$containerID].cumulativeLikes = parseInt(data.returnValues.cumulativeLikes);
		this._containerData[$containerID].users = data.returnValues.users;

		// update label
		this._updateBadge($containerID);
		
		// mark button as active
		var $likeButton = this._containerData[$containerID].likeButton.removeClass('active');
		var $dislikeButton = this._containerData[$containerID].dislikeButton.removeClass('active');

		if (data.returnValues.isLiked) {
			$likeButton.addClass('active');
			$likeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.like.active'));
			$dislikeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.dislike'));
		}
		else if (data.returnValues.isDisliked) {
			$dislikeButton.addClass('active');
			$dislikeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.dislike.active'));
			$likeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.like'));
		}
		else {
			$likeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.like'));
			$dislikeButton.find('img').attr('src', WCF.Icon.get('wcf.icon.dislike'));
		}
	},
	
	_updateBadge: function(containerID) {
		if (!this._containerData[containerID].cumulativeLikes) {
			this._containerData[containerID].badge.hide();
		}
		else {
			this._containerData[containerID].badge.show();
			
			// update like counter
			var $likesText = this._containerData[containerID].badge.find('.wcf-likesText');
			$likesText.text((this._containerData[containerID].cumulativeLikes));
			$likesText.removeClass('wcf-badgeGreen wcf-badgeRed');
			if (this._containerData[containerID].cumulativeLikes) {
				$likesText.addClass((this._containerData[containerID].cumulativeLikes > 0 ? 'wcf-badgeGreen' : 'wcf-badgeRed'));
			}
			
			// WCF.Language.get('wcf.like.button.tooltip') 
			// update tooltip
			var $users = this._containerData[containerID].users;
			var $userArray = [];
			for (var $userID in $users) $userArray.push($users[$userID].username);
			var $usersString = $userArray.join(', ');
			if ($usersString) this._containerData[containerID].badge.find('.wcf-likesText').attr('title', $usersString + ' gefaellt das.').data('tooltip', $usersString + ' gefaellt das.');
			else this._containerData[containerID].badge.find('.wcf-likesText').removeAttr('title').removeData('tooltip');
			
			var $icon = WCF.Icon.get('wcf.icon.'+(this._containerData[containerID].cumulativeLikes > 0 ? 'like' : 'dislike'));
			this._containerData[containerID].badge.find('img').attr('src', $icon);
		}
	}
});
