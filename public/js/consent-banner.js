/**
 * VIEW! Privacy — Consent banner front-end.
 * Pure vanilla JavaScript, geen jQuery of externe dependencies (§18).
 */
( function () {
	'use strict';

	if ( typeof window.viewPrivacyConfig === 'undefined' ) {
		return;
	}

	var config = window.viewPrivacyConfig;
	var COOKIE_NAME = config.cookieName || 'view_privacy_consent';

	// ------------------------------------------------------------------
	// Cookie helpers
	// ------------------------------------------------------------------

	function setCookie( name, value, days ) {
		var expires = '';
		if ( days ) {
			var date = new Date();
			date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; SameSite=Lax';
	}

	function getCookie( name ) {
		var match = document.cookie.match( new RegExp( '(^| )' + name + '=([^;]+)' ) );
		return match ? decodeURIComponent( match[ 2 ] ) : null;
	}

	function readConsent() {
		var raw = getCookie( COOKIE_NAME );
		if ( ! raw ) {
			return null;
		}
		try {
			var data = JSON.parse( raw );
			if ( data && data.v === config.consentVersion && data.categories ) {
				return data;
			}
		} catch ( e ) {
			return null;
		}
		return null;
	}

	function writeConsent( categories ) {
		var payload = {
			v: config.consentVersion,
			categories: categories,
			ts: Date.now(),
		};
		setCookie( COOKIE_NAME, JSON.stringify( payload ), config.cookieExpiryDays || 365 );
		return payload;
	}

	// ------------------------------------------------------------------
	// Server logging (optioneel, §13)
	// ------------------------------------------------------------------

	function sendToServer( categories ) {
		if ( ! config.loggingEnabled || ! config.restUrl ) {
			return;
		}
		fetch( config.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce,
			},
			body: JSON.stringify( {
				categories: categories,
				version: config.consentVersion,
			} ),
			credentials: 'same-origin',
		} ).catch( function () {
			// Stille fout: logging mag de front-end nooit blokkeren.
		} );
	}

	// ------------------------------------------------------------------
	// Consent toepassen: activeer geblokkeerde scripts/iframes (§8, §9)
	// ------------------------------------------------------------------

	function applyConsent( categories ) {
		updateConsentMode( categories );
		activateScripts( categories );
		activateEmbeds( categories );
		document.dispatchEvent(
			new CustomEvent( 'viewPrivacyConsentUpdated', { detail: { categories: categories } } )
		);
	}

	/**
	 * Stuurt een Google Consent Mode v2-update op basis van de gekozen
	 * categorieën. 'statistics' -> analytics_storage, 'marketing' ->
	 * advertising-signalen. Werkt alleen als de default-signalen al
	 * geprint zijn (zie print_consent_mode_defaults in PHP); anders is
	 * er geen gtag()-functie en doen we niets.
	 */
	function updateConsentMode( categories ) {
		if ( typeof window.gtag !== 'function' ) {
			return;
		}
		window.gtag( 'consent', 'update', {
			analytics_storage: categories.statistics ? 'granted' : 'denied',
			ad_storage: categories.marketing ? 'granted' : 'denied',
			ad_user_data: categories.marketing ? 'granted' : 'denied',
			ad_personalization: categories.marketing ? 'granted' : 'denied',
		} );
	}

	function activateScripts( categories ) {
		var blocked = document.querySelectorAll( 'script[data-view-privacy-blocked]' );
		blocked.forEach( function ( oldScript ) {
			var category = oldScript.getAttribute( 'data-view-privacy-category' );
			var serviceKey = oldScript.getAttribute( 'data-view-privacy-blocked' );
			var service = ( config.services && config.services[ serviceKey ] ) || null;

			var allowed;
			if ( service && service.container ) {
				// Containers zoals Google Tag Manager kunnen tags uit meerdere
				// categorieën bevatten; laad de container zodra minstens één
				// niet-noodzakelijke categorie is toegestaan. Welke tags daarna
				// daadwerkelijk vuren, is aan de Consent Mode-signalen (zie
				// updateConsentMode) en aan de eigen GTM-triggerconfiguratie.
				allowed = !! ( categories.statistics || categories.marketing || categories.preferences );
			} else {
				allowed = !! categories[ category ];
			}

			if ( ! allowed ) {
				return;
			}

			var newScript = document.createElement( 'script' );
			Array.prototype.forEach.call( oldScript.attributes, function ( attr ) {
				if ( attr.name === 'type' || attr.name === 'data-view-privacy-blocked' || attr.name === 'data-view-privacy-category' ) {
					return;
				}
				newScript.setAttribute( attr.name, attr.value );
			} );

			if ( oldScript.src ) {
				newScript.src = oldScript.src;
			} else {
				newScript.text = oldScript.text;
			}

			oldScript.parentNode.replaceChild( newScript, oldScript );
		} );
	}

	function activateEmbeds( categories ) {
		var placeholders = document.querySelectorAll( '.view-privacy-embed-placeholder[data-view-privacy-blocked]' );
		placeholders.forEach( function ( placeholder ) {
			var category = placeholder.getAttribute( 'data-view-privacy-category' );
			if ( ! categories[ category ] ) {
				renderPlaceholder( placeholder, category );
				return;
			}

			// Vloeiende overgang: eerst de kaart laten wegfaden, daarna de
			// iframe op zijn plaats invoegen en zelf laten infaden. Zo springt
			// de inhoud niet abrupt van placeholder naar geladen embed.
			placeholder.classList.add( 'view-privacy-embed-placeholder--leaving' );

			window.setTimeout( function () {
				if ( ! placeholder.parentNode ) {
					return; // Al vervangen (bv. dubbele aanroep); niets te doen.
				}

				var iframe = document.createElement( 'iframe' );
				iframe.className = 'view-privacy-embed-fade-in';
				iframe.src = placeholder.getAttribute( 'data-view-privacy-src' );

				var attrsRaw = placeholder.getAttribute( 'data-view-privacy-attrs' ) || '';
				applyRawAttributes( iframe, attrsRaw );

				placeholder.parentNode.replaceChild( iframe, placeholder );

				// Volgende frame: klasse verwijderen triggert de CSS-transitie naar opacity 1.
				window.requestAnimationFrame( function () {
					iframe.classList.remove( 'view-privacy-embed-fade-in' );
				} );
			}, 200 );
		} );
	}

	/**
	 * Parseer een ruwe attributenstring (afkomstig uit de PHP-buffer) en
	 * zet deze veilig op een element via setAttribute (geen innerHTML/eval).
	 */
	function applyRawAttributes( el, raw ) {
		var attrRegex = /([a-zA-Z0-9_-]+)\s*=\s*"([^"]*)"/g;
		var match;
		while ( ( match = attrRegex.exec( raw ) ) !== null ) {
			try {
				el.setAttribute( match[ 1 ], match[ 2 ] );
			} catch ( e ) {
				// Ongeldig attribuut negeren.
			}
		}
	}

	function renderPlaceholder( placeholder, category ) {
		if ( placeholder.getAttribute( 'data-view-privacy-rendered' ) === '1' ) {
			return;
		}

		var serviceKey = placeholder.getAttribute( 'data-view-privacy-blocked' );
		var serviceLabel = ( config.services && config.services[ serviceKey ] ) ? config.services[ serviceKey ].label : serviceKey;
		var embedIcon = getEmbedIcon( serviceKey );

		var iconWrap = document.createElement( 'div' );
		iconWrap.className = 'view-privacy-embed-placeholder__icon';
		iconWrap.style.background = embedIcon.bg;
		iconWrap.setAttribute( 'aria-hidden', 'true' );
		iconWrap.innerHTML = embedIcon.svg; // Vaste, interne iconenset — geen gebruikersinvoer.

		var title = document.createElement( 'p' );
		title.className = 'view-privacy-embed-placeholder__title';
		title.textContent = serviceLabel;

		var intro = document.createElement( 'p' );
		intro.className = 'view-privacy-embed-placeholder__text view-privacy-embed-placeholder__text--intro';
		intro.textContent = config.i18n.blockedIntro || '';

		var contentNoun = ( config.contentLabels && ( config.contentLabels[ serviceKey ] || config.contentLabels['default'] ) ) || '';

		var text = document.createElement( 'p' );
		text.className = 'view-privacy-embed-placeholder__text';
		text.textContent = ( config.i18n.blockedText || '' ).replace( '%s', contentNoun );

		var actions = document.createElement( 'div' );
		actions.className = 'view-privacy-embed-placeholder__actions';

		var acceptBtn = document.createElement( 'button' );
		acceptBtn.type = 'button';
		acceptBtn.className = 'vp-btn vp-btn--primary';
		acceptBtn.textContent = ( config.i18n.loadService || config.i18n.acceptService || '' ).replace( '%s', getLoadLabel( serviceKey, serviceLabel ) );
		acceptBtn.addEventListener( 'click', function () {
			var current = readConsent();
			var categories = current ? current.categories : onlyNecessary();
			var merged = {};
			Object.keys( categories ).forEach( function ( k ) {
				merged[ k ] = categories[ k ];
			} );
			merged[ category ] = true;
			finalizeConsent( merged );
		} );

		var manageBtn = document.createElement( 'button' );
		manageBtn.type = 'button';
		manageBtn.className = 'vp-btn vp-btn--text';
		manageBtn.textContent = config.i18n.blockedButton;
		manageBtn.addEventListener( 'click', function () {
			openPreferences();
		} );

		actions.appendChild( acceptBtn );
		actions.appendChild( manageBtn );

		placeholder.textContent = '';
		placeholder.appendChild( iconWrap );
		placeholder.appendChild( title );
		placeholder.appendChild( intro );
		placeholder.appendChild( text );
		placeholder.appendChild( actions );
		placeholder.setAttribute( 'data-view-privacy-rendered', '1' );
	}

	function renderAllPlaceholders( categories ) {
		var placeholders = document.querySelectorAll( '.view-privacy-embed-placeholder[data-view-privacy-blocked]' );
		placeholders.forEach( function ( placeholder ) {
			var category = placeholder.getAttribute( 'data-view-privacy-category' );
			if ( ! categories[ category ] ) {
				renderPlaceholder( placeholder, category );
			}
		} );
	}

	// ------------------------------------------------------------------
	// UI: banner (§5, §6)
	// ------------------------------------------------------------------

	var bannerEl = null;
	var overlayEl = null;
	var reopenEl = null;

	function showReopenButton() {
		if ( reopenEl || ! config.reopenIconUrl ) {
			return;
		}

		reopenEl = document.createElement( 'button' );
		reopenEl.type = 'button';
		reopenEl.className = 'vp-reopen-btn';
		reopenEl.setAttribute( 'aria-label', config.i18n.reopenLabel );
		reopenEl.setAttribute( 'title', config.i18n.reopenLabel );

		var img = document.createElement( 'img' );
		img.src = config.reopenIconUrl;
		img.alt = '';
		reopenEl.appendChild( img );

		if ( config.i18n.reopenText ) {
			var label = document.createElement( 'span' );
			label.className = 'vp-reopen-btn__label';
			label.textContent = config.i18n.reopenText;
			reopenEl.appendChild( label );
		}

		reopenEl.addEventListener( 'click', function () {
			reopenEl.classList.add( 'is-pulsing' );
			window.setTimeout( function () {
				if ( reopenEl ) {
					reopenEl.classList.remove( 'is-pulsing' );
				}
			}, 520 );
			openPreferences();
		} );

		document.body.appendChild( reopenEl );
	}

	function hideReopenButton() {
		if ( reopenEl && reopenEl.parentNode ) {
			reopenEl.parentNode.removeChild( reopenEl );
		}
		reopenEl = null;
	}

	function buildDefaultCategories() {
		var categories = {};
		Object.keys( config.categories ).forEach( function ( key ) {
			var cat = config.categories[ key ];
			categories[ key ] = !! cat.locked || !! cat.default;
		} );
		return categories;
	}

	function allCategoriesTrue() {
		var categories = {};
		Object.keys( config.categories ).forEach( function ( key ) {
			categories[ key ] = true;
		} );
		return categories;
	}

	function onlyNecessary() {
		var categories = {};
		Object.keys( config.categories ).forEach( function ( key ) {
			categories[ key ] = !! config.categories[ key ].locked;
		} );
		return categories;
	}

	function finalizeConsent( categories ) {
		// Detecteer of een eerder toegestane categorie nu wordt ingetrokken.
		// Eenmaal geactiveerde scripts/iframes (bv. een afgespeelde YouTube-
		// video) kunnen niet betrouwbaar teruggezet worden naar hun originele
		// geblokkeerde staat (de brontekst is al vervangen). Bij intrekking
		// verversen we daarom de pagina: die wordt server-side opnieuw
		// geblokkeerd en client-side correct opgebouwd volgens de nieuwe,
		// ingetrokken toestemming.
		var previous = readConsent();
		var revoked = false;
		if ( previous && previous.categories ) {
			Object.keys( previous.categories ).forEach( function ( key ) {
				if ( previous.categories[ key ] && ! categories[ key ] ) {
					revoked = true;
				}
			} );
		}

		writeConsent( categories );
		sendToServer( categories );

		if ( revoked ) {
			window.location.reload();
			return;
		}

		applyConsent( categories );
		closeBanner();
		closePreferences();
		showReopenButton();
	}

	/**
	 * Afhandeling van de eenvoudige melding (alleen functionele cookies,
	 * geen toestemmingsvraag). Legt vast dat de melding is gezien, zonder
	 * categoriekeuzes en zonder de herstelknop te tonen (er valt niets
	 * aan te passen).
	 */
	function finalizeSimpleAck() {
		var categories = onlyNecessary();
		writeConsent( categories );
		sendToServer( categories );
		closeBanner();
	}

	function makeRetentionNote() {
		if ( ! config.i18n.retentionNote ) {
			return null;
		}
		var p = document.createElement( 'p' );
		p.className = 'vp-retention-note';
		p.textContent = config.i18n.retentionNote;
		return p;
	}

	function makeFooterLine() {
		if ( ! config.footerText ) {
			return null;
		}
		var p = document.createElement( 'p' );
		p.className = 'vp-footer-line';
		p.textContent = config.footerText;
		return p;
	}

	function createBanner() {
		var b = config.banner;
		var el = document.createElement( 'div' );
		el.className = 'vp-banner vp-banner--' + b.position;
		if ( b.tagline ) {
			el.className += ' vp-banner--has-tagline';
		}
		if ( b.shadow ) {
			el.className += ' vp-banner--shadow';
		}
		if ( b.animation ) {
			el.className += ' vp-banner--animate';
		}
		el.setAttribute( 'role', 'dialog' );
		el.setAttribute( 'aria-modal', 'false' );
		el.setAttribute( 'aria-label', b.title );

		applyStyleVars( el );

		// Bewust geen sluit-X: de banner mag alleen verdwijnen via een
		// expliciete keuze (Alles accepteren / Alles weigeren / Voorkeuren).
		if ( b.tagline ) {
			var tagline = document.createElement( 'p' );
			tagline.className = 'vp-banner__tagline';
			tagline.textContent = b.tagline;
			el.appendChild( tagline );
		}

		var header = document.createElement( 'div' );
		header.className = 'vp-banner__header';

		if ( b.logo ) {
			var logo = document.createElement( 'img' );
			logo.className = 'vp-banner__logo';
			logo.src = b.logo;
			logo.alt = '';
			header.appendChild( logo );
		}

		var title = document.createElement( 'p' );
		title.className = 'vp-banner__title';
		title.textContent = b.title;
		header.appendChild( title );

		var heading = null;
		if ( b.heading ) {
			heading = document.createElement( 'p' );
			heading.className = 'vp-banner__heading';
			heading.textContent = b.heading;
		}

		var text = document.createElement( 'p' );
		text.className = 'vp-banner__text';
		text.textContent = b.text;

		var actions = document.createElement( 'div' );
		actions.className = 'vp-banner__actions';

		var acceptBtn = makeButton( config.i18n.acceptAll, 'vp-btn--primary', function () {
			finalizeConsent( allCategoriesTrue() );
		} );
		var rejectBtn = makeButton( config.i18n.rejectAll, 'vp-btn--secondary', function () {
			finalizeConsent( onlyNecessary() );
		} );
		var prefsBtn = makeButton( config.i18n.preferences, 'vp-btn--text', function () {
			openPreferences();
		} );

		actions.appendChild( acceptBtn );
		actions.appendChild( rejectBtn );
		actions.appendChild( prefsBtn );

		var privacyLink = makePrivacyLink();
		if ( privacyLink ) {
			actions.appendChild( privacyLink );
		}

		el.appendChild( header );
		if ( heading ) {
			el.appendChild( heading );
		}
		el.appendChild( text );
		el.appendChild( actions );

		var retentionNote = makeRetentionNote();
		if ( retentionNote ) {
			el.appendChild( retentionNote );
		}

		var footerLine = makeFooterLine();
		if ( footerLine ) {
			el.appendChild( footerLine );
		}

		return el;
	}

	function makePrivacyLink() {
		if ( ! config.privacyPolicyUrl ) {
			return null;
		}
		var a = document.createElement( 'a' );
		a.href = config.privacyPolicyUrl;
		a.target = '_blank';
		a.rel = 'noopener noreferrer';
		a.className = 'vp-btn vp-btn--secondary';
		a.textContent = config.i18n.privacyLink;
		return a;
	}

	/**
	 * Eenvoudige banner voor sites die uitsluitend noodzakelijke/functionele
	 * cookies plaatsen: geen categorieën, geen toestemmingsvraag, enkel een
	 * melding met één sluitknop (en optioneel de privacybeleid-link).
	 */

	function applyStyleVars( el ) {
		var b = config.banner;
		el.style.setProperty( '--vp-bg', b.color_bg );
		el.style.setProperty( '--vp-text', b.color_text );
		el.style.setProperty( '--vp-accent', b.color_accent );
		el.style.setProperty( '--vp-btn-primary-text', b.button_primary_text || '#ffffff' );
		el.style.setProperty( '--vp-btn-primary-bg', b.button_primary_bg || b.color_accent );
		el.style.setProperty( '--vp-btn-primary-border', b.button_primary_border || b.color_accent );
		el.style.setProperty( '--vp-btn-secondary-text', b.button_secondary_text || b.color_text );
		el.style.setProperty( '--vp-btn-secondary-bg', b.button_secondary_bg || b.color_bg );
		el.style.setProperty( '--vp-btn-secondary-border', b.button_secondary_border || b.color_accent );
		el.style.setProperty( '--vp-font-size', b.font_size + 'px' );
		el.style.setProperty( '--vp-radius', b.border_radius + 'px' );
		if ( b.use_theme_font ) {
			el.style.fontFamily = 'inherit';
		}
	}

	function makeButton( label, cls, handler ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'vp-btn ' + cls;
		btn.textContent = label;
		btn.addEventListener( 'click', handler );
		return btn;
	}

	function showBanner() {
		if ( bannerEl ) {
			return;
		}
		bannerEl = createBanner();
		document.body.appendChild( bannerEl );
	}

	function closeBanner() {
		if ( bannerEl && bannerEl.parentNode ) {
			bannerEl.parentNode.removeChild( bannerEl );
		}
		bannerEl = null;
	}

	// ------------------------------------------------------------------
	// UI: voorkeurencentrum (§7)
	// ------------------------------------------------------------------

	/**
	 * Lijniconen per categorie (§ "Categorie-iconen"). Simpele, uniforme
	 * SVG's (24x24, stroke-based) zodat ze op elke schermresolutie scherp
	 * blijven en meekleuren via CSS (.vp-category__icon { color }).
	 */
	var CATEGORY_ICONS = {
		necessary: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
		statistics: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M12 20V4"/><path d="M20 20v-7"/><path d="M4 20h16"/></svg>',
		marketing: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a1 1 0 001 1h2l4 4V6L6 10H4a1 1 0 00-1 1z"/><path d="M15 8a4 4 0 010 8"/><path d="M18 5a8 8 0 010 14"/></svg>',
		preferences: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9.5l5 2.5-5 2.5v-5z"/></svg>',
	};

	/**
	 * Herkenbare dienst-iconen voor de placeholder-kaart bij geblokkeerde
	 * embeds (§ "Placeholder-stijl"). Eenvoudige, generieke vormen die de
	 * dienst herkenbaar maken zonder een exacte merklogo te kopiëren.
	 * Onbekende diensten vallen terug op een neutraal "extern"-icoon.
	 */
	var EMBED_ICONS = {
		youtube: {
			bg: '#ffe3e3',
			svg: '<svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="4" fill="#ff0000"/><path d="M10 9l6 3-6 3V9z" fill="#ffffff"/></svg>',
		},
		google_maps: {
			bg: '#e2f0fe',
			svg: '<svg viewBox="0 0 24 24"><path d="M12 2C7.6 2 4 5.6 4 10c0 5.6 8 12 8 12s8-6.4 8-12c0-4.4-3.6-8-8-8z" fill="#ea4335"/><circle cx="12" cy="10" r="3" fill="#ffffff"/></svg>',
		},
		'default': {
			bg: '#eceeec',
			svg: '<svg viewBox="0 0 24 24" fill="none" stroke="#5a615c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9.5l5 2.5-5 2.5v-5z"/></svg>',
		},
	};

	function getEmbedIcon( serviceKey ) {
		return EMBED_ICONS[ serviceKey ] || EMBED_ICONS['default'];
	}

	/**
	 * Vriendelijke, kortere benaming voor op de laadknop van de placeholder-
	 * kaart (bv. "Kaart laden" i.p.v. "Google Maps laden"). Komt uit de
	 * (vertaalbare) PHP-config; onbekende diensten vallen terug op hun
	 * normale dienstlabel.
	 */
	function getLoadLabel( serviceKey, serviceLabel ) {
		return ( config.loadLabels && config.loadLabels[ serviceKey ] ) || serviceLabel;
	}

	function getCategoryIcon( key ) {
		return CATEGORY_ICONS[ key ] || CATEGORY_ICONS.preferences;
	}

	function openPreferences() {
		if ( overlayEl ) {
			return;
		}

		try {
			buildAndShowPreferences();
		} catch ( err ) {
			// Bij een onverwachte fout tijdens het opbouwen van het voorkeurencentrum
			// niet stilzwijgend vastlopen: eventuele halfopgebouwde overlay
			// opruimen en de staat resetten, zodat een volgend klik het gewoon
			// opnieuw probeert in plaats van voorgoed niets meer te doen.
			if ( overlayEl && overlayEl.parentNode ) {
				overlayEl.parentNode.removeChild( overlayEl );
			}
			overlayEl = null;
			if ( window.console && window.console.error ) {
				window.console.error( 'VIEW! Privacy: het voorkeurencentrum kon niet worden geopend.', err );
			}
		}
	}

	function buildAndShowPreferences() {
		hideReopenButton();

		var current = readConsent();
		var currentCategories = current ? current.categories : buildDefaultCategories();

		overlayEl = document.createElement( 'div' );
		overlayEl.className = 'vp-overlay';

		var modal = document.createElement( 'div' );
		modal.className = 'vp-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		applyStyleVars( modal );

		var modalHeader = document.createElement( 'div' );
		modalHeader.className = 'vp-banner__header';

		if ( config.banner.logo ) {
			var modalLogo = document.createElement( 'img' );
			modalLogo.className = 'vp-banner__logo';
			modalLogo.src = config.banner.logo;
			modalLogo.alt = '';
			modalHeader.appendChild( modalLogo );
		}

		var title = document.createElement( 'p' );
		title.className = 'vp-banner__title vp-modal__title';
		title.textContent = config.i18n.preferences;
		modalHeader.appendChild( title );

		modal.appendChild( modalHeader );

		var toggles = {};

		Object.keys( config.categories ).forEach( function ( key ) {
			var cat = config.categories[ key ];
			var descId = 'vp-cat-desc-' + key;

			var row = document.createElement( 'div' );
			row.className = 'vp-category';

			var top = document.createElement( 'div' );
			top.className = 'vp-category__row';

			var expandBtn = document.createElement( 'button' );
			expandBtn.type = 'button';
			expandBtn.className = 'vp-category__expand';
			expandBtn.setAttribute( 'aria-expanded', 'false' );
			expandBtn.setAttribute( 'aria-controls', descId );

			var labelGroup = document.createElement( 'span' );
			labelGroup.className = 'vp-category__label-group';

			var icon = document.createElement( 'span' );
			icon.className = 'vp-category__icon';
			icon.setAttribute( 'aria-hidden', 'true' );
			icon.innerHTML = getCategoryIcon( key ); // Vast, intern iconenset — geen gebruikersinvoer.

			var label = document.createElement( 'span' );
			label.className = 'vp-category__label';
			label.textContent = cat.label;

			var chevron = document.createElement( 'span' );
			chevron.className = 'vp-category__chevron';
			chevron.setAttribute( 'aria-hidden', 'true' );
			chevron.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';

			labelGroup.appendChild( icon );
			labelGroup.appendChild( label );
			expandBtn.appendChild( labelGroup );
			expandBtn.appendChild( chevron );

			var switchLabel = document.createElement( 'label' );
			switchLabel.className = 'vp-switch';

			var input = document.createElement( 'input' );
			input.type = 'checkbox';
			input.checked = cat.locked ? true : !! currentCategories[ key ];
			input.disabled = !! cat.locked;

			var track = document.createElement( 'span' );
			track.className = 'vp-switch__track';

			switchLabel.appendChild( input );
			switchLabel.appendChild( track );

			top.appendChild( expandBtn );
			top.appendChild( switchLabel );

			var desc = document.createElement( 'p' );
			desc.className = 'vp-category__desc';
			desc.id = descId;
			desc.hidden = true;
			desc.textContent = cat.description;

			expandBtn.addEventListener( 'click', function () {
				var isOpen = expandBtn.getAttribute( 'aria-expanded' ) === 'true';
				expandBtn.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
				row.classList.toggle( 'is-open', ! isOpen );
				desc.hidden = isOpen;
			} );

			row.appendChild( top );
			row.appendChild( desc );
			modal.appendChild( row );

			toggles[ key ] = input;
		} );

		var actions = document.createElement( 'div' );
		actions.className = 'vp-modal__actions';

		var saveBtn = makeButton( config.i18n.save, 'vp-btn--primary', function () {
			var categories = {};
			Object.keys( toggles ).forEach( function ( key ) {
				categories[ key ] = toggles[ key ].checked;
			} );
			finalizeConsent( categories );
		} );
		var acceptBtn = makeButton( config.i18n.acceptAll, 'vp-btn--secondary', function () {
			finalizeConsent( allCategoriesTrue() );
		} );
		var rejectBtn = makeButton( config.i18n.rejectAll, 'vp-btn--secondary', function () {
			finalizeConsent( onlyNecessary() );
		} );

		actions.appendChild( saveBtn );
		actions.appendChild( acceptBtn );
		actions.appendChild( rejectBtn );

		var modalPrivacyLink = makePrivacyLink();
		if ( modalPrivacyLink ) {
			actions.appendChild( modalPrivacyLink );
		}

		modal.appendChild( actions );

		var modalRetentionNote = makeRetentionNote();
		if ( modalRetentionNote ) {
			modal.appendChild( modalRetentionNote );
		}

		var modalFooterLine = makeFooterLine();
		if ( modalFooterLine ) {
			modal.appendChild( modalFooterLine );
		}

		overlayEl.appendChild( modal );
		document.body.appendChild( overlayEl );

		// De oorspronkelijke banner mag niet naast het voorkeurencentrum
		// blijven staan. closeBanner() is veilig om altijd aan te roepen:
		// als er geen banner zichtbaar is (heropenknop, placeholder-link,
		// externe trigger na eerder gegeven toestemming), gebeurt er niets.
		// Bewust pas ná het succesvol tonen van het voorkeurencentrum, zodat
		// een eventuele fout hierboven (opgevangen door openPreferences())
		// nooit resulteert in een pagina zonder banner én zonder voorkeurencentrum.
		closeBanner();

		// Sluiten bij klik op overlay (buiten modal) voorkomt een dode-end modal
		// voor toegankelijkheid; voorkeuren worden niet stilzwijgend opgeslagen.
		overlayEl.addEventListener( 'click', function ( e ) {
			if ( e.target === overlayEl ) {
				closePreferences();
			}
		} );

		trapFocus( modal );
	}

	function closePreferences() {
		if ( overlayEl && overlayEl.parentNode ) {
			overlayEl.parentNode.removeChild( overlayEl );
		}
		overlayEl = null;

		if ( readConsent() ) {
			showReopenButton();
		} else {
			// Er is (nog) geen keuze gemaakt: de banner mag niet stilzwijgend
			// wegblijven nu deze bij het openen van het voorkeurencentrum is
			// gesloten. Toon hem opnieuw, zodat de bezoeker alsnog een
			// expliciete keuze kan maken.
			showBanner();
		}
	}

	// ------------------------------------------------------------------
	// Toegankelijkheid: eenvoudige focus trap (WCAG 2.2 AA, §17)
	// ------------------------------------------------------------------

	function trapFocus( container ) {
		var focusable = container.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
		if ( ! focusable.length ) {
			return;
		}
		focusable[ 0 ].focus();

		container.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Tab' ) {
				return;
			}
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		} );
	}

	// ------------------------------------------------------------------
	// Init
	// ------------------------------------------------------------------

	function applyGlobalStyleVars() {
		var b = config.banner;
		var root = document.documentElement.style;
		root.setProperty( '--vp-bg', b.color_bg );
		root.setProperty( '--vp-text', b.color_text );
		root.setProperty( '--vp-accent', b.color_accent );
	}

	function init() {
		applyGlobalStyleVars();

		var consent = readConsent();

		if ( consent ) {
			applyConsent( consent.categories );
			renderAllPlaceholders( consent.categories );
			showReopenButton();
			return;
		}

		renderAllPlaceholders( onlyNecessary() );

		// Op mobiele schermen krijgt de bezoeker eerst kort de tijd om de
		// taalkeuze in het menu te maken. De banner wordt alleen vertraagd
		// qua zichtbaarheid; blokkering van niet-noodzakelijke inhoud blijft
		// vanaf het eerste moment actief. Desktop blijft ongewijzigd.
		var isMobile = window.matchMedia && window.matchMedia( '(max-width: 767px)' ).matches;
		if ( isMobile ) {
			window.setTimeout( function () {
				// Als er intussen toch consent is gegeven, toon de banner niet meer.
				if ( ! readConsent() ) {
					showBanner();
				}
			}, 5000 );
		} else {
			showBanner();
		}
	}

	// Maak "Cookies aanpassen" ook programmatisch/handmatig bruikbaar,
	// bv. via een link in de footer: <a href="#" data-view-privacy-open>.
	document.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest( '[data-view-privacy-open]' );
		if ( trigger ) {
			e.preventDefault();
			openPreferences();
		}
	} );

	window.viewPrivacy = {
		openPreferences: openPreferences,
		getConsent: readConsent,
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
