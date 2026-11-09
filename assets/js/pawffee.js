/**
 * Pawffee Break — motion layer.
 *
 * Deliberately dependency-free. Everything here is IntersectionObserver,
 * requestAnimationFrame and transforms, so the theme ships without a build
 * step and without pulling a third-party animation library into a WordPress
 * install.
 *
 * Note on smooth scrolling: a transform-based scroll hijack was considered and
 * left out on purpose. It fights `position: sticky` on the header, breaks
 * in-page anchors and confuses screen readers. Native scrolling plus
 * scroll-linked effects gets most of the feel with none of that cost.
 *
 * @package Pawffee_Child
 */

( function () {
	'use strict';

	var root = document.documentElement;
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var finePointer = window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

	/** Functions run on every animation frame while the page scrolls. */
	var scrollHandlers = [];

	function each( selector, fn, scope ) {
		Array.prototype.forEach.call( ( scope || document ).querySelectorAll( selector ), fn );
	}

	function clamp( v, min, max ) {
		return Math.min( max, Math.max( min, v ) );
	}

	function safeSession( method, key, value ) {
		try {
			return window.sessionStorage[ method ]( key, value );
		} catch ( e ) {
			return null;
		}
	}

	/* ---------------------------------------------------------------------
	 * Intro loader + page transitions
	 * ------------------------------------------------------------------ */

	/**
	 * Plays the first-visit intro: the wordmark rises letter by letter while a
	 * counter runs to 100, then two panels sweep up and away. The whole thing
	 * is just over a second. Resolves as the panels start to lift, so the hero
	 * animates in underneath them rather than after them.
	 *
	 * @return {Promise}
	 */
	function playIntro() {
		return new Promise( function ( resolve ) {
			var loader = document.querySelector( '.pw-loader' );

			if ( ! loader || ! root.classList.contains( 'pw-intro' ) ) {
				if ( loader ) {
					loader.remove();
				}
				resolve();
				return;
			}

			safeSession( 'setItem', 'pw-intro-seen', '1' );

			var count = loader.querySelector( '.pw-loader__count' );
			var bar = loader.querySelector( '.pw-loader__bar' );
			var start = performance.now();
			var duration = 750;

			function frame( now ) {
				var t = clamp( ( now - start ) / duration, 0, 1 );
				var eased = 1 - Math.pow( 1 - t, 3 );

				if ( count ) {
					count.textContent = Math.round( eased * 100 );
				}
				if ( bar ) {
					bar.style.transform = 'scaleX(' + eased.toFixed( 3 ) + ')';
				}

				if ( t < 1 ) {
					requestAnimationFrame( frame );
					return;
				}

				loader.classList.add( 'is-done' );
				root.classList.remove( 'pw-intro' );
				window.setTimeout( resolve, 120 );
				window.setTimeout( function () {
					loader.remove();
				}, 1000 );
			}

			requestAnimationFrame( frame );
		} );
	}

	/**
	 * Two-panel page transition. On an internal click a crema panel and an
	 * espresso panel sweep up over the page carrying the destination's name;
	 * navigation fires as they land (~0.35s). The next page starts covered —
	 * the inline head script sees the session flag before first paint — and
	 * the panels continue upward and off, revealing it.
	 */
	function initTransitions() {
		var curtain = document.querySelector( '.pw-curtain' );

		if ( ! curtain ) {
			return;
		}

		var label = curtain.querySelector( '.pw-curtain__label' );

		if ( root.classList.contains( 'pw-arrive' ) ) {
			safeSession( 'removeItem', 'pw-nav' );

			// A timer rather than requestAnimationFrame: rAF never fires in a
			// background tab, which would leave the page covered.
			window.setTimeout( function () {
				root.classList.add( 'pw-lifting' );
				root.classList.remove( 'pw-arrive' );
				window.setTimeout( function () {
					root.classList.remove( 'pw-lifting' );
				}, 900 );
			}, 40 );
		}

		if ( reduceMotion ) {
			return;
		}

		/**
		 * The name shown on the curtain: the link's own words, trimmed.
		 *
		 * @param {Element} link Clicked link.
		 * @return {string}
		 */
		function labelFor( link ) {
			var text = ( link.getAttribute( 'aria-label' ) || link.textContent || '' ).replace( /\s+/g, ' ' ).trim();
			var site = document.querySelector( '.wp-block-site-title' );

			if ( ! text || text.length > 32 || link.closest( '.wp-block-site-title' ) ) {
				return site ? site.textContent.trim() : 'Pawffee Break';
			}

			return text;
		}

		document.addEventListener( 'click', function ( e ) {
			if ( e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) {
				return;
			}

			var link = e.target.closest && e.target.closest( 'a[href]' );

			if ( ! link || link.target === '_blank' || link.hasAttribute( 'download' ) ) {
				return;
			}

			var url;

			try {
				url = new URL( link.href, window.location.href );
			} catch ( err ) {
				return;
			}

			if (
				url.origin !== window.location.origin ||
				url.pathname.indexOf( '/wp-admin' ) === 0 ||
				url.pathname.indexOf( '/wp-login' ) === 0 ||
				/\.(pdf|jpe?g|png|gif|webp|zip)$/i.test( url.pathname ) ||
				( url.pathname === window.location.pathname && url.search === window.location.search )
			) {
				return;
			}

			e.preventDefault();

			var name = labelFor( link );

			if ( label ) {
				label.textContent = name;
			}

			safeSession( 'setItem', 'pw-nav', name );
			root.classList.remove( 'pw-lifting' );
			root.classList.add( 'pw-leaving' );

			window.setTimeout( function () {
				window.location.href = url.href;
			}, 360 );
		} );

		// Coming back through the back/forward cache restores the covered
		// state, so clear it.
		window.addEventListener( 'pageshow', function ( e ) {
			if ( e.persisted ) {
				root.classList.remove( 'pw-leaving', 'pw-arrive', 'pw-lifting' );
			}
		} );
	}

	/**
	 * Swaps the lightweight YouTube preview for the real player on click.
	 */
	function initVideoFacades() {
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest && e.target.closest( '[data-pw-yt]' );

			if ( ! btn ) {
				return;
			}

			var iframe = document.createElement( 'iframe' );
			iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( btn.getAttribute( 'data-pw-yt' ) ) + '?autoplay=1&rel=0';
			iframe.title = btn.getAttribute( 'aria-label' ) || 'YouTube video';
			iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
			iframe.allowFullscreen = true;
			btn.replaceWith( iframe );
		} );

		// Warm the YouTube connection only once someone shows interest.
		each( '[data-pw-yt]', function ( btn ) {
			btn.addEventListener( 'pointerenter', function warm() {
				btn.removeEventListener( 'pointerenter', warm );
				[ 'https://www.youtube-nocookie.com', 'https://i.ytimg.com' ].forEach( function ( href ) {
					var link = document.createElement( 'link' );
					link.rel = 'preconnect';
					link.href = href;
					document.head.appendChild( link );
				} );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Reveal on scroll
	 * ------------------------------------------------------------------ */

	/**
	 * Reveals top-level content blocks as they enter the viewport, staggering
	 * the tagged elements inside each so a section assembles rather than
	 * appearing all at once.
	 */
	function initReveals() {
		var blocks = [];
		var content = document.querySelector( '.wp-block-post-content' );

		if ( content ) {
			blocks = blocks.concat( Array.prototype.slice.call( content.children ) );
		}

		each( '.wp-block-post-template > li', function ( item ) {
			if ( blocks.indexOf( item ) === -1 ) {
				blocks.push( item );
			}
		} );

		// Anything tagged outside those blocks (template-level heroes, the
		// footer) is observed on its own, or it would stay hidden.
		each( '[data-pw-reveal]', function ( el ) {
			var covered = blocks.some( function ( block ) {
				return block.contains( el );
			} );

			if ( ! covered ) {
				blocks.push( el );
			}
		} );

		if ( ! blocks.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) || reduceMotion ) {
			blocks.forEach( function ( block ) {
				block.classList.add( 'pw-in' );
				staggerChildren( block );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					entry.target.classList.add( 'pw-in' );
					staggerChildren( entry.target );
					observer.unobserve( entry.target );
				} );
			},
			{
				rootMargin: '0px 0px -10% 0px',
				threshold: 0.02
			}
		);

		blocks.forEach( function ( block ) {
			// The hero is revealed by the intro sequence, not by scrolling.
			if ( ! block.classList.contains( 'pw-hero' ) ) {
				observer.observe( block );
			}
		} );
	}

	/**
	 * Applies an incremental transition delay to the revealable elements
	 * inside a section.
	 *
	 * @param {Element} section Section whose children should stagger in.
	 */
	function staggerChildren( section ) {
		var i = 0;

		each( '[data-pw-reveal]:not(.pw-in)', function ( el ) {
			el.style.setProperty( '--pw-delay', Math.min( i * 0.08, 0.6 ).toFixed( 2 ) + 's' );
			el.classList.add( 'pw-in' );
			i++;
		}, section );
	}

	/**
	 * Tags the elements that should stagger inside each section, and marks
	 * images for the clip-path wipe.
	 */
	function tagRevealTargets() {
		var scopes = [
			document.querySelector( 'main' ) || document.querySelector( '.wp-block-post-content' ),
			document.querySelector( '.pw-footer' )
		];

		var selector = [
			'.wp-block-heading',
			'.wp-block-image',
			'.wp-block-buttons',
			'.wp-block-quote',
			'.wp-block-embed',
			'.wp-block-table',
			'.is-style-text-subtitle',
			'.is-style-text-annotation',
			'.pw-stat__num',
			'.pw-stat__label',
			'.pw-card__meta',
			'.pw-page-intro',
			'.pw-fact',
			'.pw-pet__summary',
			'.pw-pet__note',
			'.pw-step',
			'.pw-footer__cols .wp-block-column'
		].join( ',' );

		scopes.forEach( function ( scope ) {
			if ( ! scope ) {
				return;
			}

			each( selector, function ( el ) {
				// The hero animates via split text, and the showcase image is
				// driven by scroll position — leave both alone.
				if ( el.closest( '.pw-hero' ) || el.closest( '.pw-showcase' ) || el.closest( '.pw-step' ) !== el && el.closest( '.pw-step' ) ) {
					return;
				}

				el.setAttribute( 'data-pw-reveal', '' );

				if ( el.classList.contains( 'wp-block-image' ) && ! el.classList.contains( 'pw-parallax' ) ) {
					el.classList.add( 'pw-wipe' );
				}
			}, scope );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Hero
	 * ------------------------------------------------------------------ */

	/**
	 * Promotes the first section of the front page into a full-height hero,
	 * adds the eyebrow and scroll cue, and animates the headline in by word.
	 *
	 * @param {Promise} ready Resolves when the intro curtain has lifted.
	 */
	function initHero( ready ) {
		if ( ! document.body.classList.contains( 'home' ) ) {
			var title = document.querySelector( '.pw-page-title' );

			if ( title ) {
				var pageHero = title.closest( '.pw-page-hero' );
				splitWords( title );

				if ( pageHero ) {
					pageHero.classList.add( 'pw-hero' );
				}

				ready.then( function () {
					requestAnimationFrame( function () {
						title.classList.add( 'is-in' );

						if ( pageHero ) {
							pageHero.classList.add( 'pw-in' );
						}
					} );
				} );
			}
			return;
		}

		var content = document.querySelector( '.wp-block-post-content' );

		if ( ! content || ! content.firstElementChild ) {
			return;
		}

		var hero = content.firstElementChild;
		hero.classList.add( 'pw-hero' );

		var heading = hero.querySelector( 'h1, h2' );

		if ( heading ) {
			var eyebrow = document.createElement( 'p' );
			eyebrow.className = 'pw-hero__eyebrow';
			eyebrow.textContent = 'Coffee & Companions · Hamilton, Ontario';
			heading.parentNode.insertBefore( eyebrow, heading );

			splitWords( heading );
		}

		var cue = document.createElement( 'div' );
		cue.className = 'pw-hero__cue';
		cue.setAttribute( 'aria-hidden', 'true' );
		cue.innerHTML = '<span></span>Scroll';
		hero.appendChild( cue );

		ready.then( function () {
			requestAnimationFrame( function () {
				hero.classList.add( 'pw-in' );

				if ( heading ) {
					heading.classList.add( 'is-in' );
				}
			} );
		} );
	}

	/**
	 * Wraps every word of an element in a masked span so the words can slide
	 * up from behind their own clipping box. Inline emphasis (<em>) survives
	 * the split, so italic accents in a headline keep their styling.
	 *
	 * @param {Element} el Element whose text should be split.
	 */
	function splitWords( el ) {
		var text = el.textContent.trim();

		if ( ! text ) {
			return;
		}

		var words = [];

		Array.prototype.forEach.call( el.childNodes, function ( node ) {
			var italic = node.nodeType === 1 && node.tagName === 'EM';

			node.textContent.split( /\s+/ ).forEach( function ( w ) {
				if ( w ) {
					words.push( { text: w, italic: italic } );
				}
			} );
		} );

		// Screen readers get the sentence once, from a hidden copy; the
		// animated word spans below are aria-hidden. (aria-label isn't
		// allowed on a plain paragraph, so it can't carry the text.)
		el.classList.add( 'pw-split' );
		el.textContent = '';
		el.appendChild( srOnly( text ) );

		words.forEach( function ( word, i ) {
			var mask = document.createElement( 'span' );
			mask.className = 'pw-split__mask';
			mask.setAttribute( 'aria-hidden', 'true' );

			var inner = document.createElement( word.italic ? 'em' : 'span' );
			inner.className = 'pw-split__word';
			inner.textContent = word.text;
			inner.style.setProperty( '--pw-delay', ( 0.1 + i * 0.07 ).toFixed( 3 ) + 's' );

			mask.appendChild( inner );
			el.appendChild( mask );

			if ( i < words.length - 1 ) {
				el.appendChild( document.createTextNode( ' ' ) );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Marquee
	 * ------------------------------------------------------------------ */

	/**
	 * Inserts the scrolling strip beneath the showcase image (or the hero).
	 *
	 * The track holds the phrase list twice so the -50% keyframe lands exactly
	 * on the seam and the loop reads as continuous. Its speed nudges with the
	 * scroll velocity for a little extra life.
	 */
	function initMarquee() {
		var anchor = document.querySelector( '.pw-showcase' ) || document.querySelector( '.pw-home-hero' );

		if ( ! anchor || ! document.body.classList.contains( 'home' ) ) {
			return;
		}

		var phrases = [ 'Sip', 'Relax', 'Adopt', 'Freshly Brewed', 'Rescue Friends', 'Slow Mornings' ];
		var marquee = document.createElement( 'div' );
		marquee.className = 'pw-marquee';
		marquee.setAttribute( 'aria-hidden', 'true' );

		var track = document.createElement( 'div' );
		track.className = 'pw-marquee__track';

		for ( var pass = 0; pass < 2; pass++ ) {
			phrases.forEach( function ( phrase, i ) {
				var item = document.createElement( 'span' );
				item.className = 'pw-marquee__item' + ( i % 2 ? ' is-italic' : '' );
				item.textContent = phrase;
				track.appendChild( item );
			} );
		}

		marquee.appendChild( track );
		anchor.parentNode.insertBefore( marquee, anchor.nextSibling );
	}

	/* ---------------------------------------------------------------------
	 * Scroll-driven effects
	 * ------------------------------------------------------------------ */

	/**
	 * Reading-progress hairline across the top of the viewport.
	 */
	function initProgress() {
		var bar = document.querySelector( '.pw-progress' );

		if ( ! bar ) {
			return;
		}

		scrollHandlers.push( function ( y, vh ) {
			var max = document.documentElement.scrollHeight - vh;
			bar.style.transform = 'scaleX(' + ( max > 0 ? clamp( y / max, 0, 1 ) : 0 ).toFixed( 4 ) + ')';
		} );
	}

	/**
	 * The showcase image opens from an inset, rounded frame to full bleed as
	 * it scrolls into view, while the photo inside settles from a slight zoom.
	 */
	function initExpand() {
		each( '.pw-expand', function ( figure ) {
			var img = figure.querySelector( 'img' );

			scrollHandlers.push( function ( y, vh ) {
				var rect = figure.getBoundingClientRect();

				if ( rect.bottom < -vh || rect.top > vh * 2 ) {
					return;
				}

				var p = clamp( ( vh - rect.top ) / ( vh * 0.95 ), 0, 1 );
				var inset = ( 1 - p ) * 9;
				var radius = ( 1 - p ) * 28;

				figure.style.clipPath = 'inset(' + ( inset * 0.6 ).toFixed( 2 ) + '% ' + inset.toFixed( 2 ) + '% round ' + radius.toFixed( 1 ) + 'px)';

				if ( img ) {
					img.style.transform = 'scale(' + ( 1.18 - p * 0.18 ).toFixed( 4 ) + ') translate3d(0,' + ( ( rect.top / vh ) * -6 ).toFixed( 2 ) + '%,0)';
				}
			} );
		} );
	}

	/**
	 * Gentle vertical drift on images marked for parallax.
	 */
	function initParallax() {
		each( '.pw-parallax img, .pw-about__float img', function ( img ) {
			var speed = img.closest( '.pw-about__float' ) ? -0.14 : 0.06;

			scrollHandlers.push( function ( y, vh ) {
				var rect = img.parentNode.getBoundingClientRect();

				if ( rect.bottom < 0 || rect.top > vh ) {
					return;
				}

				var offset = ( rect.top + rect.height / 2 - vh / 2 ) * speed;
				img.style.translate = '0 ' + offset.toFixed( 1 ) + 'px';
			} );
		} );
	}

	/**
	 * A visually hidden copy of some text, for screen readers.
	 *
	 * @param {string} text
	 * @return {HTMLSpanElement}
	 */
	function srOnly( text ) {
		var span = document.createElement( 'span' );
		span.className = 'pw-sr-only';
		span.textContent = text;
		return span;
	}

	/**
	 * The manifesto paragraph lights up word by word as it passes through
	 * the middle of the viewport.
	 */
	function initManifesto() {
		each( '.pw-manifesto', function ( el ) {
			var text = el.textContent.trim();
			var words = text.split( /\s+/ );

			el.textContent = '';
			el.appendChild( srOnly( text ) );

			var spans = words.map( function ( word, i ) {
				var span = document.createElement( 'span' );
				span.className = 'pw-manifesto__word';
				span.setAttribute( 'aria-hidden', 'true' );
				span.textContent = word;
				el.appendChild( span );

				if ( i < words.length - 1 ) {
					el.appendChild( document.createTextNode( ' ' ) );
				}

				return span;
			} );

			var lit = -1;

			scrollHandlers.push( function ( y, vh ) {
				var rect = el.getBoundingClientRect();
				var p = clamp( ( vh * 0.85 - rect.top ) / ( rect.height + vh * 0.35 ), 0, 1 );
				var target = Math.round( p * spans.length );

				if ( target === lit ) {
					return;
				}

				lit = target;
				spans.forEach( function ( span, i ) {
					span.classList.toggle( 'is-lit', i < target );
				} );
			} );
		} );
	}

	/**
	 * Single rAF-throttled scroll loop feeding every registered handler.
	 */
	function startScrollLoop() {
		if ( ! scrollHandlers.length ) {
			return;
		}

		var ticking = false;

		function run() {
			var y = window.scrollY;
			var vh = window.innerHeight;

			scrollHandlers.forEach( function ( fn ) {
				fn( y, vh );
			} );
			ticking = false;
		}

		function request() {
			if ( ! ticking ) {
				ticking = true;
				requestAnimationFrame( run );
			}
		}

		window.addEventListener( 'scroll', request, { passive: true } );
		window.addEventListener( 'resize', request );
		run();
	}

	/* ---------------------------------------------------------------------
	 * Count-up numbers
	 * ------------------------------------------------------------------ */

	function initCounters() {
		if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				observer.unobserve( entry.target );

				var el = entry.target;
				var end = parseInt( el.getAttribute( 'data-pw-count' ), 10 );
				var start = performance.now();

				( function frame( now ) {
					var t = clamp( ( now - start ) / 1400, 0, 1 );
					el.textContent = Math.round( ( 1 - Math.pow( 1 - t, 4 ) ) * end );

					if ( t < 1 ) {
						requestAnimationFrame( frame );
					}
				}( start ) );
			} );
		}, { threshold: 0.6 } );

		each( '.pw-stat__num', function ( el ) {
			var value = el.textContent.trim();

			if ( /^\d+$/.test( value ) ) {
				el.setAttribute( 'data-pw-count', value );
				el.textContent = '0';
				observer.observe( el );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Live café status + clock
	 * ------------------------------------------------------------------ */

	/**
	 * Shows whether the café is open right now, in Hamilton's time zone, and
	 * keeps the footer's local clock ticking.
	 */
	function initLiveStatus() {
		var statuses = document.querySelectorAll( '.pw-open-status' );
		var clocks = document.querySelectorAll( '.pw-clock' );

		if ( ! statuses.length && ! clocks.length ) {
			return;
		}

		// Café hours, mirrored from the Hours page. 0 = Sunday.
		var hours = {
			0: [ 9, 17 ], 1: [ 8, 18 ], 2: [ 8, 18 ], 3: [ 8, 18 ],
			4: [ 8, 18 ], 5: [ 8, 18 ], 6: [ 9, 17 ]
		};
		var days = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];

		function fmtHour( h ) {
			return ( h % 12 || 12 ) + ( h < 12 ? 'am' : 'pm' );
		}

		function hamiltonNow() {
			var parts = {};

			try {
				new Intl.DateTimeFormat( 'en-CA', {
					timeZone: 'America/Toronto',
					weekday: 'short',
					hour: 'numeric',
					minute: '2-digit',
					hourCycle: 'h23'
				} ).formatToParts( new Date() ).forEach( function ( p ) {
					parts[ p.type ] = p.value;
				} );
			} catch ( e ) {
				var d = new Date();
				return { day: d.getDay(), hour: d.getHours(), minute: d.getMinutes() };
			}

			return {
				day: days.indexOf( parts.weekday.replace( '.', '' ).slice( 0, 3 ) ),
				hour: parseInt( parts.hour, 10 ) % 24,
				minute: parseInt( parts.minute, 10 )
			};
		}

		function update() {
			var now = hamiltonNow();
			var today = hours[ now.day ] || hours[ 1 ];
			var mins = now.hour * 60 + now.minute;
			var open = mins >= today[ 0 ] * 60 && mins < today[ 1 ] * 60;

			var state;
			var detail;

			if ( open ) {
				// Flag the last hour so people know to hurry.
				var closingSoon = today[ 1 ] * 60 - mins <= 60;
				state = closingSoon ? 'Closing soon' : 'Open now';
				detail = 'until ' + fmtHour( today[ 1 ] );
				root.classList.toggle( 'pw-closing-soon', closingSoon );
			} else {
				var opensToday = mins < today[ 0 ] * 60;
				var next = opensToday ? today : hours[ ( now.day + 1 ) % 7 ];
				state = 'Closed';
				detail = 'opens ' + ( opensToday ? '' : 'tomorrow ' ) + fmtHour( next[ 0 ] );
				root.classList.remove( 'pw-closing-soon' );
			}

			Array.prototype.forEach.call( statuses, function ( status ) {
				status.setAttribute( 'aria-label', state + ', ' + detail );
				status.innerHTML =
					'<span class="pw-open-status__state">' + state + '</span>' +
					'<span class="pw-open-status__sep" aria-hidden="true"></span>' +
					'<span class="pw-open-status__detail">' + detail + '</span>';
			} );

			// Highlight today on the hours table (rows run Monday → Sunday).
			each( '.pw-hours tbody tr', function ( row, i ) {
				row.classList.toggle( 'is-today', ( i + 1 ) % 7 === now.day );
			} );
			root.classList.toggle( 'pw-is-open', open );

			Array.prototype.forEach.call( clocks, function ( clock ) {
				var h = now.hour;
				clock.textContent = ( h % 12 || 12 ) + ':' + ( now.minute < 10 ? '0' : '' ) + now.minute + ( h < 12 ? ' am' : ' pm' );
			} );
		}

		update();
		window.setInterval( update, 30000 );
	}

	/* ---------------------------------------------------------------------
	 * Custom cursor
	 * ------------------------------------------------------------------ */

	/**
	 * Drives the trailing cursor ring, easing toward the pointer each frame,
	 * swelling over interactive elements and showing a label over cards.
	 */
	function initCursor() {
		var cursor = document.querySelector( '.pawffee-cursor' );

		if ( ! cursor || reduceMotion ) {
			return;
		}

		if ( ! finePointer ) {
			cursor.remove();
			return;
		}

		var label = cursor.querySelector( '.pawffee-cursor__label' );
		var targetX = window.innerWidth / 2;
		var targetY = window.innerHeight / 2;
		var x = targetX;
		var y = targetY;
		var running = false;

		document.addEventListener( 'pointermove', function ( e ) {
			targetX = e.clientX;
			targetY = e.clientY;

			if ( ! cursor.classList.contains( 'is-visible' ) ) {
				x = targetX;
				y = targetY;
				cursor.classList.add( 'is-visible' );
			}

			if ( ! running ) {
				running = true;
				requestAnimationFrame( tick );
			}
		}, { passive: true } );

		document.documentElement.addEventListener( 'mouseleave', function () {
			cursor.classList.remove( 'is-visible' );
		} );

		document.documentElement.addEventListener( 'mouseenter', function () {
			cursor.classList.add( 'is-visible' );
		} );

		function tick() {
			x += ( targetX - x ) * 0.2;
			y += ( targetY - y ) * 0.2;

			cursor.style.transform = 'translate3d(' + x.toFixed( 2 ) + 'px,' + y.toFixed( 2 ) + 'px,0)';

			// Stop redrawing once the ring has caught up; the next pointer
			// move starts the loop again.
			if ( Math.abs( targetX - x ) < 0.1 && Math.abs( targetY - y ) < 0.1 ) {
				running = false;
				return;
			}

			requestAnimationFrame( tick );
		}

		var labelled = '.pw-card, .pw-stories .wp-block-post-featured-image, .pw-expand';
		var interactive = 'a, button, input[type="submit"], .wp-block-button__link, summary, label, select';

		document.addEventListener( 'pointerover', function ( e ) {
			if ( ! e.target.closest ) {
				return;
			}

			var card = e.target.closest( labelled );

			if ( card && label ) {
				label.textContent = card.classList.contains( 'pw-expand' ) ? 'Hello' : 'View';
				cursor.classList.add( 'is-label' );
				cursor.classList.remove( 'is-active' );
				return;
			}

			cursor.classList.remove( 'is-label' );
			cursor.classList.toggle( 'is-active', !! e.target.closest( interactive ) );
		}, { passive: true } );

		document.addEventListener( 'pointerdown', function () {
			cursor.classList.add( 'is-down' );
		} );

		document.addEventListener( 'pointerup', function () {
			cursor.classList.remove( 'is-down' );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Magnetic buttons
	 * ------------------------------------------------------------------ */

	/**
	 * Pulls buttons a few pixels toward the pointer while it is over them.
	 */
	function initMagnetic() {
		if ( reduceMotion || ! finePointer ) {
			return;
		}

		each( '.wp-block-button__link', function ( button ) {
			button.parentNode.classList.add( 'pw-magnetic' );

			button.addEventListener( 'pointermove', function ( e ) {
				var rect = button.getBoundingClientRect();
				var dx = e.clientX - ( rect.left + rect.width / 2 );
				var dy = e.clientY - ( rect.top + rect.height / 2 );

				button.style.transform =
					'translate3d(' + ( dx * 0.2 ).toFixed( 2 ) + 'px,' + ( dy * 0.28 ).toFixed( 2 ) + 'px,0)';
			} );

			button.addEventListener( 'pointerleave', function () {
				button.style.transform = '';
			} );
		} );
	}

	/**
	 * Cards tilt very slightly toward the pointer.
	 */
	function initTilt() {
		if ( reduceMotion || ! finePointer ) {
			return;
		}

		each( '.pw-card .wp-block-image', function ( card ) {
			card.addEventListener( 'pointermove', function ( e ) {
				var rect = card.getBoundingClientRect();
				var px = ( e.clientX - rect.left ) / rect.width - 0.5;
				var py = ( e.clientY - rect.top ) / rect.height - 0.5;

				card.style.transform = 'perspective(900px) rotateY(' + ( px * 5 ).toFixed( 2 ) + 'deg) rotateX(' + ( py * -5 ).toFixed( 2 ) + 'deg)';
			} );

			card.addEventListener( 'pointerleave', function () {
				card.style.transform = '';
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Header state
	 * ------------------------------------------------------------------ */

	/**
	 * Condenses the sticky header once the page has scrolled, and tucks it
	 * away while scrolling down so content gets the whole screen.
	 */
	function initHeaderState() {
		var header = document.querySelector( '.wp-site-blocks > header' );
		var lastY = window.scrollY;

		function measureHeader() {
			if ( header ) {
				root.style.setProperty(
					'--pw-header-h',
					Math.round( header.getBoundingClientRect().height ) + 'px'
				);
			}
		}

		measureHeader();

		if ( 'ResizeObserver' in window && header ) {
			new ResizeObserver( measureHeader ).observe( header );
		} else {
			window.addEventListener( 'resize', measureHeader );
		}

		scrollHandlers.push( function ( y ) {
			root.classList.toggle( 'pw-scrolled', y > 40 );

			// Ignore jitter; only react to a deliberate scroll.
			if ( Math.abs( y - lastY ) > 6 ) {
				var menuOpen = document.querySelector( '.wp-block-navigation__responsive-container.is-menu-open' );
				root.classList.toggle( 'pw-header-hidden', y > lastY && y > 400 && ! menuOpen );
				lastY = y;
			}
		} );
	}

	/**
	 * Smooth return to the top from the footer link.
	 */
	function initActiveNav() {
		var here = window.location.pathname.replace( /\/+$/, '/' );

		each( '.wp-block-navigation-item', function ( item ) {
			var link = item.querySelector( 'a[href]' );

			if ( ! link || item.classList.contains( 'current-menu-item' ) ) {
				return;
			}

			var path;

			try {
				path = new URL( link.href, window.location.href ).pathname.replace( /\/+$/, '/' );
			} catch ( e ) {
				return;
			}

			// Single stories live under the journal, so it stays lit there.
			var isJournal = /brewed-stories\/$/.test( path ) && document.body.classList.contains( 'single-post' );

			if ( path === here || isJournal ) {
				item.classList.add( 'current-menu-item' );
				link.setAttribute( 'aria-current', 'page' );
			}
		} );
	}

	function initToTop() {
		each( '.pw-totop', function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				window.scrollTo( { top: 0, behavior: reduceMotion ? 'auto' : 'smooth' } );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */

	function init() {
		root.classList.add( 'pw-booted' );

		var ready = playIntro();

		// Order matters. The hero claims the first section before the reveal
		// pass tags anything, so hero contents are excluded from the stagger.
		// And the marquee is injected before the observer starts, because the
		// stylesheet hides every direct child of the content area by default —
		// a node added afterwards would be hidden with nothing watching to
		// bring it back.
		initHero( ready );
		initMarquee();
		tagRevealTargets();
		initReveals();
		initCounters();
		initLiveStatus();
		initTransitions();
		initVideoFacades();
		initToTop();
		initActiveNav();
		initHeaderState();
		initProgress();

		if ( ! reduceMotion ) {
			initExpand();
			initParallax();
			initManifesto();
		} else {
			each( '.pw-manifesto', function ( el ) {
				el.classList.add( 'is-static' );
			} );
		}

		startScrollLoop();
		initCursor();
		initMagnetic();
		initTilt();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
