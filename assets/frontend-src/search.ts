import { __, sprintf } from '@wordpress/i18n';

interface SearchResult {
	id: number;
	title: string;
	url: string;
	excerpt: string;
}

interface SearchResponse {
	count: number;
	results: SearchResult[];
}

export function initSearch( root: HTMLElement ) {
	const modal = root.querySelector< HTMLElement >(
		'[data-itsdz-search-modal]'
	);
	const input = root.querySelector< HTMLInputElement >(
		'[data-itsdz-search-input]'
	);
	const status = root.querySelector< HTMLElement >(
		'[data-itsdz-search-status]'
	);
	const results = root.querySelector< HTMLElement >(
		'[data-itsdz-search-results]'
	);
	let trigger: HTMLElement | null = null;
	let timer = 0;
	let controller: AbortController | null = null;

	if ( ! modal || ! input || ! status || ! results ) {
		return { open: () => undefined };
	}

	const closeButtons = root.querySelectorAll< HTMLElement >(
		'[data-itsdz-search-close]'
	);

	const close = () => {
		modal.hidden = true;
		document.documentElement.classList.remove( 'itsdz-modal-open' );
		trigger?.focus();
	};

	const renderResults = ( items: SearchResult[] ) => {
		results.replaceChildren();
		items.forEach( ( item ) => {
			const link = document.createElement( 'a' );
			const title = document.createElement( 'strong' );
			const excerpt = document.createElement( 'span' );
			link.href = item.url;
			title.textContent = item.title;
			excerpt.textContent = item.excerpt;
			link.append( title, excerpt );
			results.appendChild( link );
		} );
	};

	const search = async () => {
		const query = input.value.trim();
		if ( query.length < 2 ) {
			status.textContent = __(
				'Type at least two characters to search.',
				'doczur'
			);
			results.replaceChildren();
			return;
		}

		controller?.abort();
		controller = new AbortController();
		status.textContent = __( 'Searching…', 'doczur' );

		try {
			const url = new URL( `${ root.dataset.restUrl }search` );
			url.searchParams.set( 'kb_id', root.dataset.kbId || '0' );
			url.searchParams.set( 'q', query );
			url.searchParams.set( 'limit', '10' );
			const response = await fetch( url, { signal: controller.signal } );
			if ( ! response.ok ) {
				throw new Error();
			}
			const payload = ( await response.json() ) as SearchResponse;
			renderResults( payload.results );
			status.textContent = sprintf(
				/* translators: %d: number of search results. */
				__( '%d results found.', 'doczur' ),
				payload.count
			);
		} catch ( error ) {
			if (
				error instanceof DOMException &&
				error.name === 'AbortError'
			) {
				return;
			}
			status.textContent = __(
				'Search is temporarily unavailable.',
				'doczur'
			);
		}
	};

	input.addEventListener( 'input', () => {
		window.clearTimeout( timer );
		timer = window.setTimeout( () => void search(), 220 );
	} );
	closeButtons.forEach( ( button ) =>
		button.addEventListener( 'click', close )
	);
	modal.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			close();
			return;
		}

		if ( event.key === 'Tab' ) {
			const focusable = Array.from(
				modal.querySelectorAll< HTMLElement >(
					'input, button, a[href]'
				)
			).filter( ( element ) => ! element.hasAttribute( 'disabled' ) );
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];
			const active = root.ownerDocument.activeElement;

			if ( event.shiftKey && active === first ) {
				event.preventDefault();
				last?.focus();
			} else if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				first?.focus();
			}
		}
	} );

	return {
		open: () => {
			trigger = root.ownerDocument.activeElement as HTMLElement | null;
			modal.hidden = false;
			document.documentElement.classList.add( 'itsdz-modal-open' );
			input.focus();
		},
	};
}
