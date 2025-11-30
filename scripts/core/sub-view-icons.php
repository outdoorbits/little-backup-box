<?php
/*
# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################*/
?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="display:none">
<!-- 	social media -->
	<?php
		// core_icons
		$mastodon = <<<HTML
<symbol id="ICONID" viewBox="0 0 24 24" fill="none">
    BACKGROUND
    <g transform="translate(4,4)">
        <path d="M11.19 12.195c2.016-.24 3.77-1.475 3.99-2.603.348-1.778.32-4.339.32-4.339 0-3.47-2.286-4.488-2.286-4.488C12.062.238 10.083.017 8.027 0h-.05C5.92.017 3.942.238 2.79.765c0 0-2.285 1.017-2.285 4.488l-.002.662c-.004.64-.007 1.35.011 2.091.083 3.394.626 6.74 3.78 7.57 1.454.383 2.703.463 3.709.408 1.823-.1 2.847-.647 2.847-.647l-.06-1.317s-1.303.41-2.767.36c-1.45-.05-2.98-.156-3.215-1.928a4 4 0 0 1-.033-.496s1.424.346 3.228.428c1.103.05 2.137-.064 3.188-.189zm1.613-2.47H11.13v-4.08c0-.859-.364-1.295-1.091-1.295-.804 0-1.207.517-1.207 1.541v2.233H7.168V5.89c0-1.024-.403-1.541-1.207-1.541-.727 0-1.091.436-1.091 1.296v4.079H3.197V5.522q0-1.288.66-2.046c.456-.505 1.052-.764 1.793-.764.856 0 1.504.328 1.933.983L8 4.39l.417-.695c.429-.655 1.077-.983 1.934-.983.74 0 1.336.259 1.791.764q.662.757.661 2.046z" fill="#6364FF" stroke="none"/>
    </g>
</symbol>
HTML;

		$telegram = <<<HTML
<symbol id="ICONID" viewBox="0 0 24 24" fill="none">
  BACKGROUND
  <g transform="translate(4,4)">
    <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z" fill="#FFFFFF" stroke="#229ED9" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
  </g>
</symbol>
HTML;

		$matrix = <<<HTML
<symbol id="ICONID" viewBox="0 0 24 24" fill="none">
	BACKGROUND
	<g stroke="#000" stroke-width="1.8" stroke-linecap="butt" stroke-linejoin="miter" fill="none">
		<!-- linke Klammer -->
		<path d="M4 5 h3 M4 5 v14 M4 19 h3" />
		<!-- rechte Klammer -->
		<path d="M20 5 h-3 M20 5 v14 M20 19 h-3" />
	</g>
	<!-- zentriertes kleines m -->
	<text
		x="12" y="12"
		font-family="sans-serif"
		font-size="9"
		text-anchor="middle"
		dominant-baseline="middle"
		fill="#000">
		m
	</text>
</symbol>
HTML;

		$bluesky = <<<HTML
<symbol id="ICONID" viewBox="0 0 24 24" fill="none">
	BACKGROUND
	<g transform="translate(4,4)">
		<path d="M3.468 1.948C5.303 3.325 7.276 6.118 8 7.616c.725-1.498 2.698-4.29 4.532-5.668C13.855.955 16 .186 16 2.632c0 .489-.28 4.105-.444 4.692-.572 2.04-2.653 2.561-4.504 2.246 3.236.551 4.06 2.375 2.281 4.2-3.376 3.464-4.852-.87-5.23-1.98-.07-.204-.103-.3-.103-.218 0-.081-.033.014-.102.218-.379 1.11-1.855 5.444-5.231 1.98-1.778-1.825-.955-3.65 2.28-4.2-1.85.315-3.932-.205-4.503-2.246C.28 6.737 0 3.12 0 2.632 0 .186 2.145.955 3.468 1.948" fill="#5488f0" stroke="none"/>
	</g>
</symbol>
HTML;

		// backgrounds
		$nothing	= '<rect x="2" y="2" width="20" height="20" rx="3" ry="3" fill="#dadada"/>';
		$publish	= '<rect x="2" y="2" width="20" height="20" rx="3" ry="3" fill="#ff6363"/>';
		$published	= '<rect x="2" y="2" width="20" height="20" rx="3" ry="3" fill="#7dfa75"/>';

		$combined	= <<<HTML
<defs>
	<linearGradient id="grad-bg" x1="0%" y1="100%" x2="100%" y2="0%">
	<stop offset="0%" stop-color="#7dfa75"/>
	<stop offset="50%" stop-color="#7dfa75"/>
	<stop offset="50%" stop-color="#ff6363"/>
	<stop offset="100%" stop-color="#ff6363"/>
	</linearGradient>
</defs>
<rect x="2" y="2" width="20" height="20" rx="3" ry="3" fill="url(#grad-bg)"/>
HTML;

		$core_icons		= array(
			'mastodon'	=> $mastodon,
			'matrix'	=> $matrix,
			'telegram'	=> $telegram,
			'bluesky'	=> $bluesky
		);

		$backgrounds	= array(
			'nothing'	=> $nothing,
			'publish'	=> $publish,
			'published'	=> $published,
			'combined'	=> $combined
		);

		foreach($core_icons as $iconNAME => $iconCODE) {
			echo("\n<!-- ${iconNAME} -->\n");
			foreach($backgrounds as $bgNAME => $bgCODE) {
				$CODE	= str_replace('ICONID', "icon-${iconNAME}-${bgNAME}", $iconCODE);
				$CODE	= str_replace('BACKGROUND', $bgCODE, $CODE);
				echo($CODE);
			}
		}
	?>

<!-- 	grid -->
	<symbol id="icon-columns-one" viewBox="0 0 24 24">
		<rect x="3" y="3" width="18" height="5" rx="1" fill="#8BD3FF"/>
		<rect x="3" y="6.4" width="18" height="1.2" fill="#2AA1FF" opacity=".85"/>
		<circle cx="6.2" cy="4.6" r="0.8" fill="#FFD34D"/>
		<path d="M3 8 L8.2 4.2 L12 8 Z" fill="#2E7D32"/>

		<rect x="3" y="9.5" width="18" height="5" rx="1" fill="#A3E1FF"/>
		<circle cx="12" cy="10.3" r="0.8" fill="#FFC94A"/>
		<path d="M3 14.5 L7.2 10.6 L10 12.7 L13.2 10.2 L21 14.5 Z" fill="#3FA34D"/>

		<rect x="3" y="16" width="18" height="5" rx="1" fill="#7BC6FF"/>
		<circle cx="19" cy="17" r="0.7" fill="#FFB84C"/>
		<path d="M3 21 L10 16.5 L13.8 18.8 L21 21 Z" fill="#4C8B3F"/>
	</symbol>

	<symbol id="icon-columns-multi" viewBox="0 0 24 24">
		<rect x="3" y="3" width="5" height="5" rx="0.8" fill="#8BD3FF"/>
		<circle cx="7.4" cy="3.8" r="0.5" fill="#FFD34D"/>
		<path d="M3 8 L5.2 3.8 L8 8 Z" fill="#3D8B3D"/>

		<rect x="9.5" y="3" width="5" height="5" rx="0.8" fill="#A3E1FF"/>
		<path d="M9.5 8 L11.2 6.2 L12.6 6.8 L14 5.6 L14.5 5.9 L14.5 8 Z" fill="#409E4D"/>
		<circle cx="13.8" cy="3.7" r="0.45" fill="#FFC94A"/>

		<rect x="16" y="3" width="5" height="5" rx="0.8" fill="#7BC6FF"/>
		<rect x="16" y="6.1" width="5" height="1.1" fill="#2AA1FF" opacity=".9"/>
		<path d="M16 8 L18.6 4.6 L21 8 Z" fill="#2F7A33"/>
		<circle cx="20.2" cy="3.7" r="0.45" fill="#FFD34D"/>

		<rect x="3" y="9.5" width="5" height="5" rx="0.8" fill="#A8E6FF"/>
		<path d="M3 14.5 L5 10.6 L6.3 12.3 L8 10.2 L8 14.5 Z" fill="#3FA34D"/>
		<circle cx="6.8" cy="10.2" r="0.45" fill="#FFB84C"/>

		<rect x="9.5" y="9.5" width="5" height="5" rx="0.8" fill="#8BD3FF"/>
		<circle cx="10.1" cy="10.1" r="0.45" fill="#FFD34D"/>
		<path d="M9.5 14.5 L12.2 11.1 L14.5 12.9 L14.5 14.5 Z" fill="#4B9650"/>

		<rect x="16" y="9.5" width="5" height="5" rx="0.8" fill="#9EDCFF"/>
		<rect x="16" y="12.5" width="5" height="0.9" fill="#2AA1FF" opacity=".85"/>
		<path d="M16 14.5 L17.6 12.4 L19 12.9 L20.4 11.8 L21 12.1 L21 14.5 Z" fill="#3E8E45"/>

		<rect x="3" y="16" width="5" height="5" rx="0.8" fill="#7FCFFF"/>
		<circle cx="7.6" cy="16.6" r="0.45" fill="#FFC94A"/>
		<path d="M3 21 L5.9 17.5 L8 19.2 L8 21 Z" fill="#3D8741"/>

		<rect x="9.5" y="16" width="5" height="5" rx="0.8" fill="#A3E1FF"/>
		<circle cx="12" cy="16.7" r="0.45" fill="#FFD34D"/>
		<path d="M9.5 21 L11.1 18.3 L12.3 19.1 L13.7 17.6 L14.5 18.1 L14.5 21 Z" fill="#449C4E"/>

		<rect x="16" y="16" width="5" height="5" rx="0.8" fill="#71C0FF"/>
		<path d="M16 21 L18.8 18.2 L19.9 19.1 L21 18.4 L21 21 Z" fill="#2F7A33"/>
		<circle cx="20.5" cy="16.7" r="0.42" fill="#FFB84C"/>
	</symbol>

<!-- 	commented -->
	<symbol id="icon-comment" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		<path d="M21 15a4 4 0 0 1-4 4H9l-4 4v-4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"/>
		<circle cx="10" cy="11" r="1.25"/><circle cx="14" cy="11" r="1.25"/><circle cx="18" cy="11" r="1.25"/>
	</symbol>

<!-- 	maximise -->
	<symbol id="icon-maximise" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		<line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		<polyline points="14,20 20,20 20,14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

		<line x1="4" y1="20" x2="20" y2="4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		<polyline points="14,4 20,4 20,10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	</symbol>

<!-- 	rating -->
	<!-- common star shape -->
	<path id="star-shape" d="M12 3.6l2.62 5.3 5.85.85-4.23 4.12 1 5.8L12 17.5 6.76 19.7l1-5.8L3.53 9.75l5.85-.85L12 3.6z"/>

	<!-- star, filled -->
	<symbol id="icon-star-filled" viewBox="0 0 24 24">
		<use href="#star-shape" fill="currentColor"/>
	</symbol>

	<!-- star, filled, default -->
	<symbol id="icon-star-filled-default" viewBox="0 0 24 24">
		<use href="#star-shape" fill="currentColor"/>
		<circle cx="12" cy="22" r="2" fill="currentColor"/>
	</symbol>

	<!-- star, outlined -->
	<symbol id="icon-star-outline" viewBox="0 0 24 24">
		<use href="#star-shape"
			fill="none"
			stroke="currentColor"
			stroke-width="2"
			stroke-linejoin="round"
			stroke-linecap="round"
			transform="translate(12 12) scale(0.92) translate(-12 -12)"/>
	</symbol>

	<!-- star, outlined, default -->
	<symbol id="icon-star-outline-default" viewBox="0 0 24 24">
		<use href="#star-shape"
			fill="none"
			stroke="currentColor"
			stroke-width="2"
			stroke-linejoin="round"
			stroke-linecap="round"
			transform="translate(12 12) scale(0.92) translate(-12 -12)"/>
			<circle cx="12" cy="22" r="2" fill="currentColor"/>
	</symbol>

	<!-- Reject-X -->
	<symbol id="icon-reject" viewBox="0 0 24 24">
		<path d="M6 7 L18 19 M18 7 L6 19"
			stroke="currentColor" stroke-width="2.4"
			stroke-linecap="round" fill="none"
			transform="translate(0,1)"/>
	</symbol>

<!-- 	move in image list -->
	<symbol id="icon-move-left" viewBox="0 0 24 24">
		<path d="M15 6L9 12L15 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	</symbol>

	<symbol id="icon-move-right" viewBox="0 0 24 24">
		<path d="M9 6L15 12L9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	</symbol>

	<symbol id="icon-move-first" viewBox="0 0 24 24">
		<path d="M17 6L11 12L17 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		<line x1="7" y1="6" x2="7" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
	</symbol>

	<symbol id="icon-move-last" viewBox="0 0 24 24">
		<path d="M7 6L13 12L7 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		<line x1="17" y1="6" x2="17" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
	</symbol>


</svg>
