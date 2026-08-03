import { __ } from '@wordpress/i18n';

const COLOR_KEY = 'itsdz-color-mode';

export function initNavigation( root: HTMLElement ) {
	const sidebar = root.querySelector< HTMLElement >( '[data-itsdz-sidebar]' );
	const navToggle = root.querySelector< HTMLButtonElement >(
		'[data-itsdz-nav-toggle]'
	);
	const navClose = root.querySelector< HTMLButtonElement >(
		'[data-itsdz-nav-close]'
	);
	const themeToggle = root.querySelector< HTMLButtonElement >(
		'[data-itsdz-theme-toggle]'
	);
	const copyButton = root.querySelector< HTMLButtonElement >(
		'[data-itsdz-copy-link]'
	);

	const closeNavigation = () => {
		root.classList.remove( 'is-nav-open' );
		navToggle?.setAttribute( 'aria-expanded', 'false' );
	};

	navToggle?.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		event.stopImmediatePropagation();
		const open = root.classList.toggle( 'is-nav-open' );
		navToggle.setAttribute( 'aria-expanded', String( open ) );
		if ( open ) {
			sidebar?.focus();
		}
	} );
	navClose?.addEventListener( 'click', closeNavigation );

	const preferredMode = root.dataset.theme || 'system';
	const storedMode = window.localStorage.getItem( COLOR_KEY );
	const systemDark = window.matchMedia(
		'(prefers-color-scheme: dark)'
	).matches;
	let dark = storedMode
		? storedMode === 'dark'
		: preferredMode === 'dark' ||
		  ( preferredMode === 'system' && systemDark );

	const applyColorMode = () => {
		root.dataset.colorMode = dark ? 'dark' : 'light';
		themeToggle?.setAttribute(
			'aria-label',
			dark
				? __( 'Use light mode', 'doczur' )
				: __( 'Use dark mode', 'doczur' )
		);
	};

	applyColorMode();
	themeToggle?.addEventListener( 'click', () => {
		dark = ! dark;
		window.localStorage.setItem( COLOR_KEY, dark ? 'dark' : 'light' );
		applyColorMode();
	} );

	copyButton?.addEventListener( 'click', async () => {
		try {
			await navigator.clipboard.writeText( window.location.href );
			copyButton.textContent = __( 'Copied', 'doczur' );
			window.setTimeout( () => {
				copyButton.textContent = __( 'Copy link', 'doczur' );
			}, 1800 );
		} catch {
			copyButton.textContent = __( 'Copy failed', 'doczur' );
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			closeNavigation();
		}
	} );
}
