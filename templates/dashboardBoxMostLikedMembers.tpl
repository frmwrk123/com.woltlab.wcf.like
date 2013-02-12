<ul class="sidebarBoxList">
	{foreach from=$mostLikedMembers item=likedMember}
		<li class="box24">
			<a href="{link controller='User' object=$likedMember}{/link}" class="framed">{@$likedMember->getAvatar()->getImageTag(24)}</a>
			
			<hgroup class="sidebarBoxHeadline">
				<h1><a href="{link controller='User' object=$likedMember}{/link}" class="userLink" data-user-id="{@$likedMember->userID}">{$likedMember->username}</a></h1>
				<h2><small>{lang}wcf.dashboard.box.mostLikedMembers.likes{/lang}</small></h2>
			</hgroup>
		</li>
	{/foreach}
</ul>
