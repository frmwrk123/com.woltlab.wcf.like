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
	 * shows the detailed summary of users who liked the object
	 */
	_showSummary: true,
	
	/**
	 * enables the dislike option
	 */
	_enableDislikes: true,
	
	/**
	 * Initializes like support.
	 */
	init: function(canLike, enableDislikes, showSummary) {
		this._canLike = canLike;
		this._enableDislikes = enableDislikes;
		this._showSummary = showSummary;
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
					'likes': $container.data('like-likes'),
					'dislikes': $container.data('like-dislikes'),
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
	 * @param	jQuery		badge
	 * @param	jQuery		summary
	 */
	_buildWidget: function(containerID, likeButton, dislikeButton, badge, summary) {
		var $widget = $('<aside class="likesWidget"><ul></ul></aside>');
		if (this._canLike) {
			dislikeButton.appendTo($widget.find('ul'));
			likeButton.appendTo($widget.find('ul'));
		}
		badge.appendTo($widget);
		
		this._addWidget(containerID, $widget); 
	},
	
	/**
	 * Creates the like widget.
	 * 
	 * @param	integer		containerID
	 */
	_createWidget: function(containerID) {
		var $likeButton = $('<li class="likeButton"><a title="'+WCF.Language.get('wcf.like.button.like')+'" class="jsTooltip"><img src="' + WCF.Icon.get('wcf.icon.like') + '" alt="" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.like')+'</span></a></li>');
		var $dislikeButton = $('<li class="dislikeButton"><a title="'+WCF.Language.get('wcf.like.button.dislike')+'" class="jsTooltip"><img src="' + WCF.Icon.get('wcf.icon.dislike') + '" alt="" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.dislike')+'</span></a></li>');
		var $badge = $('<a class="badge jsTooltip likesBadge"></a>');
		if (this._showSummary) var $summary = $('<p class="likesSummary"></p>');
		this._buildWidget(containerID, $likeButton, $dislikeButton, $badge, $summary);
		if (!this._enableDislikes) $dislikeButton.hide();
		
		this._containerData[containerID].likeButton = $likeButton;
		this._containerData[containerID].dislikeButton = $dislikeButton;
		this._containerData[containerID].badge = $badge;
		this._containerData[containerID].summary = $summary;
		
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
		
		this._updateBadge(containerID);
		if (this._showSummary) this._updateSummary(containerID);
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
		this._containerData[$containerID].likes = parseInt(data.returnValues.likes);
		this._containerData[$containerID].dislikes = parseInt(data.returnValues.dislikes);
		this._containerData[$containerID].users = data.returnValues.users;

		// update label
		this._updateBadge($containerID);
		// update summary
		if (this._showSummary) this._updateSummary($containerID);
		
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
		if (!this._containerData[containerID].likes && !this._containerData[containerID].dislikes) {
			this._containerData[containerID].badge.hide();
		}
		else {
			this._containerData[containerID].badge.show();
			
			// update like counter
			var $cumulativeLikes = this._containerData[containerID].likes - this._containerData[containerID].dislikes;
			var $badge = this._containerData[containerID].badge;
			$badge.removeClass('badgeGreen badgeRed');
			if ($cumulativeLikes > 0) {
				$badge.text('+' + $cumulativeLikes);
				$badge.addClass('badgeGreen');
			}
			else if ($cumulativeLikes < 0) {
				$badge.text($cumulativeLikes);
				$badge.addClass('badgeRed');
			}
			else {
				$badge.text('±0');
			}
			
			// update tooltip
			var $likes = this._containerData[containerID].likes;
			var $dislikes = this._containerData[containerID].dislikes;
			$badge.data('tooltip', eval(WCF.Language.get('wcf.like.tooltip')));
		}
	},
	
	_updateSummary: function(containerID) {
		if (!this._containerData[containerID].likes) {
			this._containerData[containerID].summary.hide();
		}
		else {
			this._containerData[containerID].summary.show();
			
			var $users = this._containerData[containerID].users;
			var $userArray = [];
			for (var $userID in $users) $userArray.push($users[$userID].username);
			var $others = this._containerData[containerID].likes - $userArray.length;
			
			this._containerData[containerID].summary.text(eval(WCF.Language.get('wcf.like.summary')));
		}
	}
});
