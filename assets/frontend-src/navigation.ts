import { __ } from '@wordpress/i18n';

const COLOR_KEY = 'itsdz-color-mode';

/**
 * navigator.clipboard is only available in a secure context (https, or the
 * literal `localhost` host) — on a plain-http dev/staging site it throws
 * synchronously. Fall back to the legacy execCommand path in that case
 * instead of silently reporting "Copy failed" for every visitor there.
 * @param text
 */
async function copyToClipboard( text: string ): Promise< void > {
	if ( window.isSecureContext && navigator.clipboard ) {
		await navigator.clipboard.writeText( text );
		return;
	}

	const textarea = document.createElement( 'textarea' );
	textarea.value = text;
	textarea.style.position = 'fixed';
	textarea.style.top = '-1000px';
	textarea.style.opacity = '0';
	document.body.appendChild( textarea );
	textarea.focus();
	textarea.select();

	try {
		if ( ! document.execCommand( 'copy' ) ) {
			throw new Error( 'execCommand copy was unsuccessful' );
		}
	} finally {
		document.body.removeChild( textarea );
	}
}

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
				? __( 'Use light mode', 'itsmanzur-docs' )
				: __( 'Use dark mode', 'itsmanzur-docs' )
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
			await copyToClipboard( window.location.href );
			copyButton.textContent = __( 'Copied', 'itsmanzur-docs' );
			window.setTimeout( () => {
				copyButton.textContent = __( 'Copy link', 'itsmanzur-docs' );
			}, 1800 );
		} catch {
			copyButton.textContent = __( 'Copy failed', 'itsmanzur-docs' );
		}
	} );

	const markdownButton = root.querySelector< HTMLButtonElement >(
		'[data-itsdz-copy-markdown]'
	);

	markdownButton?.addEventListener( 'click', async () => {
		const content = root.querySelector< HTMLElement >(
			'[data-itsdz-content]'
		);

		if ( ! content ) {
			return;
		}

		const label =
			markdownButton.textContent ?? __( 'Copy as Markdown', 'itsmanzur-docs' );

		try {
			// Loaded on demand so the converter stays out of the initial bundle.
			const { articleToMarkdown } = await import(
				/* webpackChunkName: "itsdz-markdown" */ './markdown'
			);

			await copyToClipboard(
				articleToMarkdown(
					content,
					markdownButton.dataset.itsdzTitle ?? document.title,
					window.location.href
				)
			);

			markdownButton.textContent = __( 'Copied', 'itsmanzur-docs' );
		} catch {
			markdownButton.textContent = __( 'Copy failed', 'itsmanzur-docs' );
		}

		window.setTimeout( () => {
			markdownButton.textContent = label;
		}, 1800 );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			closeNavigation();
		}
	} );

	// Close sidebar when tapping outside on mobile.
	document.addEventListener( 'click', ( event ) => {
		if (
			! root.classList.contains( 'is-nav-open' ) ||
			! sidebar ||
			sidebar.contains( event.target as Node ) ||
			navToggle?.contains( event.target as Node )
		) {
			return;
		}
		closeNavigation();
	} );
}
