/**
 * Like support for WCF
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Like = Class.extend({
	/**
	 * user can like 
	 * @var	boolean
	 */
	_canLike: false,
	
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
	 * enables the dislike option
	 */
	_enableDislikes: true,
	
	/**
	 * cached template for like details
	 * @var	object
	 */
	_likeDetails: { },
	
	/**
	 * dialog overlay for like details
	 * @var	jQuery
	 */
	_likeDetailsDialog: null,
	
	/**
	 * proxy object
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * shows the detailed summary of users who liked the object
	 */
	_showSummary: true,
	
	/**
	 * Initializes like support.
	 */
	init: function(canLike, enableDislikes, showSummary) {
		this._canLike = canLike;
		this._enableDislikes = enableDislikes;
		this._likeDetails = { };
		this._likeDetailsDialog = null;
		this._showSummary = showSummary;
		
		var $containers = this._getContainers();
		this._initContainers($containers);
		
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		// bind dom node inserted listener
		var $date = new Date();
		var $identifier = $date.toString().hashCode + $date.getUTCMilliseconds();
		WCF.DOMNodeInsertedHandler.addCallback('WCF.Like' + $identifier, $.proxy(this._domNodeInserted, this));
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
		var $likeButton = $('<li class="likeButton"><a title="'+WCF.Language.get('wcf.like.button.like')+'" class="jsTooltip"><span class="icon icon16 icon-thumbs-up" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.like')+'</span></a></li>');
		var $dislikeButton = $('<li class="dislikeButton"><a title="'+WCF.Language.get('wcf.like.button.dislike')+'" class="jsTooltip"><span class="icon icon16 icon-thumbs-down" /> <span class="invisible">'+WCF.Language.get('wcf.like.button.dislike')+'</span></a></li>');
		if (!this._enableDislikes) $dislikeButton.hide();
		
		var $badge = $('<a class="badge jsTooltip likesBadge" />').data('containerID', containerID).click($.proxy(this._showLikeDetails, this));
		
		var $summary = null;
		if (this._showSummary) $summary = $('<p class="likesSummary" />');
		this._buildWidget(containerID, $likeButton, $dislikeButton, $badge, $summary);
		
		this._containerData[containerID].likeButton = $likeButton;
		this._containerData[containerID].dislikeButton = $dislikeButton;
		this._containerData[containerID].badge = $badge;
		this._containerData[containerID].summary = $summary;
		
		$likeButton.data('containerID', containerID).data('type', 'like').click($.proxy(this._click, this));
		$dislikeButton.data('containerID', containerID).data('type', 'dislike').click($.proxy(this._click, this));
		this._setActiveState($likeButton, $dislikeButton, this._containerData[containerID].liked);
		this._updateBadge(containerID);
		if (this._showSummary) this._updateSummary(containerID);
	},
	
	/**
	 * Displays like details for an object.
	 * 
	 * @param	object		event
	 * @param	string		containerID
	 */
	_showLikeDetails: function(event, containerID) {
		var $containerID = (event === null) ? containerID : $(event.currentTarget).data('containerID');
		
		if (this._likeDetails[$containerID] === undefined) {
			this._proxy.setOption('data', {
				actionName: 'getLikeDetails',
				className: 'wcf\\data\\like\\LikeAction',
				parameters: {
					data: {
						containerID: $containerID,
						objectID: this._containerData[$containerID].objectID,
						objectType: this._containerData[$containerID].objectType
					}
				}
			});
			this._proxy.sendRequest();
		}
		else {
			if (this._likeDetailsDialog === null) {
				this._likeDetailsDialog = $('<div>' + this._likeDetails[$containerID] + '</div>').hide().appendTo(document.body);
				this._likeDetailsDialog.wcfDialog({
					title: WCF.Language.get('wcf.like.details')
				});
			}
			else {
				this._likeDetailsDialog.html(this._likeDetails[$containerID]).wcfDialog('open');
			}
		}
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
		
		switch (data.actionName) {
			case 'dislike':
			case 'like':
				// update container data
				this._containerData[$containerID].likes = parseInt(data.returnValues.likes);
				this._containerData[$containerID].dislikes = parseInt(data.returnValues.dislikes);
				this._containerData[$containerID].users = data.returnValues.users;
				
				// update label
				this._updateBadge($containerID);
				// update summary
				if (this._showSummary) this._updateSummary($containerID);
				
				// mark button as active
				var $likeButton = this._containerData[$containerID].likeButton;
				var $dislikeButton = this._containerData[$containerID].dislikeButton;
				var $likeStatus = 0;
				if (data.returnValues.isLiked) $likeStatus = 1;
				else if (data.returnValues.isDisliked) $likeStatus = -1;
				this._setActiveState($likeButton, $dislikeButton, $likeStatus);
				
				// invalidate cache for like details
				if (this._likeDetails[$containerID] !== undefined) {
					delete this._likeDetails[$containerID];
				}
			break;
			
			case 'getLikeDetails':
				this._likeDetails[$containerID] = data.returnValues.template;
				this._showLikeDetails(null, $containerID);
			break;
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
			$badge.removeClass('green red');
			if ($cumulativeLikes > 0) {
				$badge.text('+' + $cumulativeLikes);
				$badge.addClass('green');
			}
			else if ($cumulativeLikes < 0) {
				$badge.text($cumulativeLikes);
				$badge.addClass('red');
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
	},
	
	/**
	 * Sets button active state.
	 * 
	 * @param 	jquery		likeButton
	 * @param 	jquery		dislikeButton
	 * @param	integer		likeStatus
	 */
	_setActiveState: function(likeButton, dislikeButton, likeStatus) {
		likeButton.removeClass('active');
		dislikeButton.removeClass('active');
		
		if (likeStatus == 1) {
			likeButton.addClass('active');
		}
		else if (likeStatus == -1) {
			dislikeButton.addClass('active');
		}
	}
});
