import './style.css';
import { initFeedback } from './feedback';
import { initNavigation } from './navigation';
import { initToc } from './toc';

export const FRONTEND_ROOT_CLASS = 'itsdz-docs';

const root = document.querySelector< HTMLElement >(
	`.${ FRONTEND_ROOT_CLASS }`
);

if ( root ) {
	initNavigation( root );
	initToc( root );
	initFeedback( root );
	initCodeCopy( root );
	initReadingProgress( root );
	initSearchHighlight( root );

	let searchController: Promise< { open: () => void } > | null = null;
	const openSearch = () => {
		searchController ??= import( './search' ).then( ( module ) =>
			module.initSearch( root )
		);
		void searchController.then( ( controller ) => controller.open() );
	};

	root.querySelectorAll< HTMLButtonElement >(
		'[data-itsdz-search-open]'
	).forEach( ( button ) => {
		button.addEventListener( 'click', openSearch );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if (
			( event.ctrlKey || event.metaKey ) &&
			event.key.toLowerCase() === 'k'
		) {
			event.preventDefault();
			openSearch();
		}
	} );

	// Auto-open search when the page was reached via the embedded search block.
	// The block form submits as GET ?itsdz_q=query to the KB URL.
	const preQuery = new URLSearchParams( window.location.search ).get(
		'itsdz_q'
	);
	if ( preQuery ) {
		openSearch();
		// After the search module loads and the modal is visible, pre-fill the
		// input and fire an 'input' event so the search executes immediately.
		void searchController!.then( () => {
			const input = root.querySelector< HTMLInputElement >(
				'[data-itsdz-search-input]'
			);
			if ( input ) {
				window.setTimeout( () => {
					input.value = preQuery;
					input.dispatchEvent(
						new Event( 'input', { bubbles: true } )
					);
				}, 80 );
			}
		} );
	}
}

function initCodeCopy( container: HTMLElement ) {
	container.querySelectorAll< HTMLPreElement >( 'pre' ).forEach( ( pre ) => {
		if ( pre.querySelector( '.itsdz-code-copy-btn' ) ) {
			return;
		}
		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'itsdz-code-copy-btn';
		btn.textContent = 'Copy';
		btn.ariaLabel = 'Copy code to clipboard';
		btn.addEventListener( 'click', () => {
			const codeNode = pre.querySelector( 'code' );
			const text = codeNode ? codeNode.innerText : pre.innerText;
			void navigator.clipboard.writeText( text ).then( () => {
				btn.textContent = '✓ Copied!';
				btn.classList.add( 'is-copied' );
				window.setTimeout( () => {
					btn.textContent = 'Copy';
					btn.classList.remove( 'is-copied' );
				}, 2000 );
			} );
		} );
		pre.appendChild( btn );
	} );
}

/**
 * Reading Progress Bar — shows scroll progress as a thin gradient bar at top.
 */
function initReadingProgress( container: HTMLElement ) {
	const content = container.querySelector< HTMLElement >( '[data-itsdz-content]' );
	if ( ! content ) {
		return;
	}

	const bar = document.createElement( 'div' );
	bar.className = 'itsdz-reading-progress';
	bar.setAttribute( 'role', 'progressbar' );
	bar.setAttribute( 'aria-valuemin', '0' );
	bar.setAttribute( 'aria-valuemax', '100' );
	document.body.prepend( bar );

	const update = () => {
		const rect = content.getBoundingClientRect();
		const total = rect.height - window.innerHeight;
		if ( total <= 0 ) {
			bar.style.width = '100%';
			bar.setAttribute( 'aria-valuenow', '100' );
			return;
		}
		const scrolled = Math.max( 0, -rect.top );
		const pct = Math.min( 100, Math.round( ( scrolled / total ) * 100 ) );
		bar.style.width = `${ pct }%`;
		bar.setAttribute( 'aria-valuenow', String( pct ) );
	};

	window.addEventListener( 'scroll', update, { passive: true } );
	update();
}

/**
 * Search Highlight — highlights query words in article content when
 * the page was reached via search (?itsdz_q=...).
 */
function initSearchHighlight( container: HTMLElement ) {
	const query = new URLSearchParams( window.location.search ).get( 'itsdz_q' );
	if ( ! query ) {
		return;
	}

	const content = container.querySelector< HTMLElement >( '[data-itsdz-content]' );
	if ( ! content ) {
		return;
	}

	const words = query
		.trim()
		.split( /\s+/ )
		.filter( Boolean )
		.map( ( w ) => w.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) );

	if ( ! words.length ) {
		return;
	}

	const pattern = new RegExp( `(${ words.join( '|' ) })`, 'gi' );

	const walk = ( node: Node ) => {
		if ( node.nodeType === Node.TEXT_NODE ) {
			highlightTextNode( node as Text, pattern );
			return;
		}

		if ( node.nodeType !== Node.ELEMENT_NODE ) {
			return;
		}

		const element = node as Element;

		if ( element.matches( 'script, style, pre, code, textarea, mark' ) ) {
			return;
		}

		Array.from( node.childNodes ).forEach( walk );
	};

	walk( content );
}

/**
 * Wrap regex matches in <mark> elements without reparsing text as HTML.
 */
function highlightTextNode( node: Text, pattern: RegExp ) {
	const text = node.nodeValue ?? '';
	pattern.lastIndex = 0;

	const matches = [ ...text.matchAll( pattern ) ];

	if ( ! matches.length ) {
		return;
	}

	const fragment = document.createDocumentFragment();
	let lastIndex = 0;

	for ( const match of matches ) {
		const start = match.index ?? 0;
		const matched = match[ 0 ];

		if ( start > lastIndex ) {
			fragment.appendChild(
				document.createTextNode( text.slice( lastIndex, start ) )
			);
		}

		const mark = document.createElement( 'mark' );
		mark.className = 'itsdz-highlight';
		mark.textContent = matched;
		fragment.appendChild( mark );
		lastIndex = start + matched.length;
	}

	if ( lastIndex < text.length ) {
		fragment.appendChild( document.createTextNode( text.slice( lastIndex ) ) );
	}

	node.parentNode?.replaceChild( fragment, node );
}
