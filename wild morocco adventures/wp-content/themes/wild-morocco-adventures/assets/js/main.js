( function () {
	'use strict';
	const header = document.querySelector( '[data-wma-header]' );
	const scrollProgress = document.querySelector( '[data-wma-scroll-progress]' );
	const toggle = document.querySelector( '[data-wma-menu-toggle]' );
	const panel = document.querySelector( '[data-wma-menu-panel]' );
	const heroContent = document.querySelector( '.wma-home-hero-content' );
	if ( heroContent ) { window.requestAnimationFrame( function () { heroContent.classList.add( 'is-visible' ); } ); }
	if ( toggle && panel ) {
		const setMenuState = function ( open, returnFocus ) {
			toggle.setAttribute( 'aria-expanded', String( open ) );
			panel.classList.toggle( 'is-open', open );
			document.body.classList.toggle( 'wma-menu-open', open );
			const label = toggle.querySelector( '.screen-reader-text' );
			if ( label && window.wmaTheme ) { label.textContent = open ? window.wmaTheme.menuClose : window.wmaTheme.menuOpen; }
			if ( returnFocus ) { toggle.focus(); }
		};
		toggle.addEventListener( 'click', function () { setMenuState( toggle.getAttribute( 'aria-expanded' ) !== 'true', false ); } );
		panel.addEventListener( 'click', function ( event ) { if ( event.target.closest( 'a' ) && window.matchMedia( '(max-width:1023px)' ).matches ) { setMenuState( false, false ); } } );
		document.addEventListener( 'keydown', function ( event ) { if ( 'Escape' === event.key && toggle.getAttribute( 'aria-expanded' ) === 'true' ) { setMenuState( false, true ); } } );
		window.addEventListener( 'resize', function () { if ( window.innerWidth > 1023 && toggle.getAttribute( 'aria-expanded' ) === 'true' ) { setMenuState( false, false ); } }, { passive: true } );
	}
	if ( header ) {
		let scrollTicking = false;
		const updatePageChrome = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 24 );
			if ( scrollProgress ) {
				const scrollable = Math.max( 1, document.documentElement.scrollHeight - window.innerHeight );
				scrollProgress.style.transform = 'scaleX(' + Math.min( 1, Math.max( 0, window.scrollY / scrollable ) ) + ')';
			}
			scrollTicking = false;
		};
		const requestPageChromeUpdate = function () {
			if ( scrollTicking ) { return; }
			scrollTicking = true;
			window.requestAnimationFrame( updatePageChrome );
		};
		updatePageChrome();
		window.addEventListener( 'scroll', requestPageChromeUpdate, { passive: true } );
		window.addEventListener( 'resize', requestPageChromeUpdate, { passive: true } );
	}
	document.querySelectorAll( '[data-wma-gallery-thumb]' ).forEach( function ( thumb ) { thumb.addEventListener( 'click', function () { const gallery = thumb.closest( '[data-wma-gallery]' ); const main = gallery?.querySelector( '[data-wma-gallery-main]' ); if ( ! main ) { return; } main.src = thumb.dataset.full; main.srcset = ''; main.alt = thumb.querySelector( 'img' )?.alt || ''; gallery.querySelectorAll( '[data-wma-gallery-thumb]' ).forEach( ( item ) => item.setAttribute( 'aria-pressed', String( item === thumb ) ) ); } ); } );
	const tourFilters = document.querySelector( '[data-wma-tour-filters]' );
	if ( tourFilters ) {
		const updateFilterState = function ( select ) { select.closest( '.wma-filter-control' )?.classList.toggle( 'has-value', Boolean( select.value ) ); };
		const customSelects = [];
		const closeCustomSelect = function ( entry, returnFocus ) {
			entry.field.classList.remove( 'is-open' );
			entry.list.hidden = true;
			entry.trigger.setAttribute( 'aria-expanded', 'false' );
			if ( returnFocus ) { entry.trigger.focus(); }
		};
		const closeCustomSelects = function ( except ) { customSelects.forEach( function ( entry ) { if ( entry !== except ) { closeCustomSelect( entry, false ); } } ); };

		tourFilters.querySelectorAll( '.wma-filter-select-wrap select' ).forEach( function ( select, selectIndex ) {
			const wrap = select.closest( '.wma-filter-select-wrap' );
			const field = select.closest( '.wma-filter-control' );
			const label = field?.querySelector( 'label' );
			if ( ! wrap || ! field || ! label ) { return; }

			updateFilterState( select );
			const trigger = document.createElement( 'button' );
			const valueText = document.createElement( 'span' );
			const chevron = document.createElement( 'i' );
			const list = document.createElement( 'div' );
			const options = Array.from( select.options );
			const optionButtons = [];
			const baseId = select.id || 'wma-filter-select-' + selectIndex;

			label.id = label.id || baseId + '-label';
			trigger.type = 'button';
			trigger.id = baseId + '-trigger';
			trigger.className = 'wma-custom-select-trigger';
			trigger.setAttribute( 'aria-haspopup', 'listbox' );
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.setAttribute( 'aria-controls', baseId + '-choices' );
			valueText.id = baseId + '-value';
			valueText.className = 'wma-custom-select-value';
			trigger.setAttribute( 'aria-labelledby', label.id + ' ' + valueText.id );
			chevron.className = 'wma-custom-select-chevron';
			chevron.setAttribute( 'aria-hidden', 'true' );
			trigger.append( valueText, chevron );

			list.id = baseId + '-choices';
			list.className = 'wma-custom-select-list';
			list.setAttribute( 'role', 'listbox' );
			list.setAttribute( 'aria-labelledby', label.id );
			list.hidden = true;

			options.forEach( function ( option, optionIndex ) {
				const optionButton = document.createElement( 'button' );
				const mark = document.createElement( 'span' );
				const text = document.createElement( 'span' );
				optionButton.type = 'button';
				optionButton.id = baseId + '-choice-' + optionIndex;
				optionButton.className = 'wma-custom-select-option';
				optionButton.setAttribute( 'role', 'option' );
				optionButton.tabIndex = -1;
				mark.className = 'wma-custom-select-check';
				mark.textContent = '✓';
				mark.setAttribute( 'aria-hidden', 'true' );
				text.textContent = option.text;
				optionButton.append( mark, text );
				list.append( optionButton );
				optionButtons.push( optionButton );
			} );

			const entry = { field, list, trigger };
			customSelects.push( entry );
			const syncCustomSelect = function () {
				valueText.textContent = options[ select.selectedIndex ]?.text || '';
				optionButtons.forEach( function ( optionButton, optionIndex ) {
					const selected = optionIndex === select.selectedIndex;
					optionButton.classList.toggle( 'is-selected', selected );
					optionButton.setAttribute( 'aria-selected', String( selected ) );
				} );
				updateFilterState( select );
			};
			const focusOption = function ( optionIndex ) {
				const safeIndex = Math.max( 0, Math.min( optionButtons.length - 1, optionIndex ) );
				optionButtons[ safeIndex ]?.focus();
			};
			const openCustomSelect = function ( requestedIndex ) {
				closeCustomSelects( entry );
				field.classList.add( 'is-open' );
				list.hidden = false;
				trigger.setAttribute( 'aria-expanded', 'true' );
				window.requestAnimationFrame( function () { focusOption( Number.isInteger( requestedIndex ) ? requestedIndex : select.selectedIndex ); } );
			};
			const chooseOption = function ( optionIndex ) {
				select.selectedIndex = optionIndex;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				closeCustomSelect( entry, true );
			};

			trigger.addEventListener( 'click', function () { if ( field.classList.contains( 'is-open' ) ) { closeCustomSelect( entry, false ); } else { openCustomSelect( select.selectedIndex ); } } );
			trigger.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowDown' === event.key || 'ArrowUp' === event.key ) { event.preventDefault(); openCustomSelect( select.selectedIndex ); }
				if ( 'Home' === event.key ) { event.preventDefault(); openCustomSelect( 0 ); }
				if ( 'End' === event.key ) { event.preventDefault(); openCustomSelect( optionButtons.length - 1 ); }
				if ( 'Escape' === event.key ) { closeCustomSelect( entry, false ); }
			} );
			optionButtons.forEach( function ( optionButton, optionIndex ) {
				optionButton.addEventListener( 'click', function () { chooseOption( optionIndex ); } );
				optionButton.addEventListener( 'keydown', function ( event ) {
					if ( 'ArrowDown' === event.key ) { event.preventDefault(); focusOption( optionIndex + 1 ); }
					if ( 'ArrowUp' === event.key ) { event.preventDefault(); focusOption( optionIndex - 1 ); }
					if ( 'Home' === event.key ) { event.preventDefault(); focusOption( 0 ); }
					if ( 'End' === event.key ) { event.preventDefault(); focusOption( optionButtons.length - 1 ); }
					if ( 'Enter' === event.key || ' ' === event.key ) { event.preventDefault(); chooseOption( optionIndex ); }
					if ( 'Escape' === event.key ) { event.preventDefault(); closeCustomSelect( entry, true ); }
					if ( 'Tab' === event.key ) { closeCustomSelect( entry, false ); }
				} );
			} );

			select.addEventListener( 'change', syncCustomSelect );
			select.tabIndex = -1;
			select.setAttribute( 'aria-hidden', 'true' );
			label.htmlFor = trigger.id;
			wrap.append( trigger, list );
			wrap.classList.add( 'is-custom' );
			syncCustomSelect();
		} );

		document.addEventListener( 'click', function ( event ) {
			const clickedField = event.target.closest( '.wma-filter-control' );
			customSelects.forEach( function ( entry ) { if ( entry.field !== clickedField ) { closeCustomSelect( entry, false ); } } );
		} );
		tourFilters.addEventListener( 'submit', function () { tourFilters.classList.add( 'is-submitting' ); tourFilters.setAttribute( 'aria-busy', 'true' ); } );
	}
	const destinationVideos = Array.from( document.querySelectorAll( '[data-wma-destination-video]' ) );
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion:reduce)' ).matches;
	const hero = document.querySelector( '.wma-home-hero' );
	if ( hero && ! reducedMotion && window.matchMedia( '(pointer:fine)' ).matches ) {
		hero.addEventListener( 'pointermove', function ( event ) {
			const bounds = hero.getBoundingClientRect();
			const x = Math.max( 0, Math.min( 100, ( event.clientX - bounds.left ) / bounds.width * 100 ) );
			const y = Math.max( 0, Math.min( 100, ( event.clientY - bounds.top ) / bounds.height * 100 ) );
			hero.style.setProperty( '--wma-pointer-x', x.toFixed( 1 ) + '%' );
			hero.style.setProperty( '--wma-pointer-y', y.toFixed( 1 ) + '%' );
		} );
	}
	const loadDestinationVideo = function ( video ) {
		if ( video.dataset.loaded || reducedMotion ) { return; }
		const source = video.querySelector( 'source[data-src]' );
		if ( ! source ) { return; }
		source.src = source.dataset.src;
		source.removeAttribute( 'data-src' );
		video.dataset.loaded = 'true';
		video.load();
	};
	if ( destinationVideos.length && ! reducedMotion ) {
		if ( 'IntersectionObserver' in window ) {
			const videoObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					const video = entry.target;
					if ( entry.isIntersecting ) {
						loadDestinationVideo( video );
						video.play().catch( function () {} );
					} else if ( video.dataset.loaded ) {
						video.pause();
					}
				} );
			}, { rootMargin: '220px 0px', threshold: 0.05 } );
			destinationVideos.forEach( ( video ) => videoObserver.observe( video ) );
		} else {
			destinationVideos.forEach( function ( video ) { loadDestinationVideo( video ); video.play().catch( function () {} ); } );
		}
	}
	const staggerGroups = document.querySelectorAll( '.wma-trust-grid,.wma-trip-grid,.wma-destination-grid,.wma-style-grid,.wma-testimonial-grid,.wma-number-list,.wma-process-grid,.wma-value-grid,.wma-post-grid,.wma-region-list,.wma-inclusion-grid' );
	staggerGroups.forEach( function ( group ) {
		Array.from( group.children ).forEach( function ( item, index ) {
			item.setAttribute( 'data-wma-reveal', '' );
			item.style.setProperty( '--wma-reveal-delay', Math.min( index, 5 ) * 85 + 'ms' );
		} );
	} );
	document.querySelectorAll( '.wma-section-heading,.wma-story-copy,.wma-responsible-grid>div,.wma-footer-cta-inner>div' ).forEach( function ( item ) {
		item.setAttribute( 'data-wma-reveal', '' );
	} );
	document.querySelectorAll( '.wma-story-image,.wma-region-list article:nth-child(odd) .wma-region-image' ).forEach( function ( item ) { item.setAttribute( 'data-wma-reveal', '' ); item.setAttribute( 'data-wma-reveal-direction', 'left' ); } );
	document.querySelectorAll( '.wma-story-copy,.wma-region-list article:nth-child(even) .wma-region-image' ).forEach( function ( item ) { item.setAttribute( 'data-wma-reveal', '' ); item.setAttribute( 'data-wma-reveal-direction', 'right' ); } );
	if ( ! reducedMotion && window.matchMedia( '(pointer:fine)' ).matches ) {
		document.querySelectorAll( '.wma-trip-card' ).forEach( function ( card ) {
			card.addEventListener( 'pointermove', function ( event ) {
				const bounds = card.getBoundingClientRect();
				card.style.setProperty( '--wma-card-rotate-x', ( ( 0.5 - ( event.clientY - bounds.top ) / bounds.height ) * 2.2 ).toFixed( 2 ) + 'deg' );
				card.style.setProperty( '--wma-card-rotate-y', ( ( ( event.clientX - bounds.left ) / bounds.width - 0.5 ) * 2.8 ).toFixed( 2 ) + 'deg' );
				card.style.setProperty( '--wma-card-glow-x', ( ( event.clientX - bounds.left ) / bounds.width * 100 ).toFixed( 1 ) + '%' );
				card.style.setProperty( '--wma-card-glow-y', ( ( event.clientY - bounds.top ) / bounds.height * 100 ).toFixed( 1 ) + '%' );
			} );
			card.addEventListener( 'pointerleave', function () {
				card.style.removeProperty( '--wma-card-rotate-x' );
				card.style.removeProperty( '--wma-card-rotate-y' );
			} );
		} );
	}
	const notice = document.querySelector( '.wma-form-notice' ); if ( notice ) { notice.focus(); }
	const revealElements = document.querySelectorAll( '[data-wma-reveal]' );
	if ( 'IntersectionObserver' in window && ! reducedMotion ) {
		const observer = new IntersectionObserver( ( entries ) => entries.forEach( ( entry ) => { if ( entry.isIntersecting ) { entry.target.classList.add( 'is-visible' ); observer.unobserve( entry.target ); } } ), { rootMargin: '0px 0px -6% 0px', threshold: 0.08 } );
		revealElements.forEach( ( element ) => observer.observe( element ) );
	} else {
		revealElements.forEach( ( element ) => element.classList.add( 'is-visible' ) );
	}
}() );
