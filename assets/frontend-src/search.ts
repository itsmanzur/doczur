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

	const renderResults = ( items: SearchResult[], query: string ) => {
		results.replaceChildren();

		if ( items.length === 0 ) {
			const empty = document.createElement( 'p' );
			empty.className = 'itsdz-search-empty';
			empty.textContent = sprintf(
				/* translators: %s: the search query that returned no results. */
				__( 'No results for "%s". Try different keywords.', 'itsmanzur-docs' ),
				query
			);
			results.appendChild( empty );
			renderPopular( results );
			return;
		}

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
				'itsmanzur-docs'
			);
			results.replaceChildren();
			return;
		}

		controller?.abort();
		controller = new AbortController();
		status.textContent = __( 'Searching…', 'itsmanzur-docs' );

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
			renderResults( payload.results, query );
			status.textContent = sprintf(
				/* translators: %d: number of search results. */
				__( '%d results found.', 'itsmanzur-docs' ),
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
				'itsmanzur-docs'
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

interface PopularLink {
	title: string;
	url: string;
}

function renderPopular( results: HTMLElement ) {
	let popular: PopularLink[] = [];

	try {
		popular = JSON.parse(
			results.dataset.itsdzPopular || '[]'
		) as PopularLink[];
	} catch ( error ) {
		popular = [];
	}

	if ( ! Array.isArray( popular ) || ! popular.length ) {
		appendFeedbackHint( results );
		return;
	}

	const heading = document.createElement( 'p' );
	heading.className = 'itsdz-search-popular-label';
	heading.textContent = __( 'Popular articles', 'itsmanzur-docs' );
	results.appendChild( heading );

	popular.slice( 0, 5 ).forEach( ( item ) => {
		if ( ! item?.title || ! item?.url ) {
			return;
		}

		const link = document.createElement( 'a' );
		link.href = item.url;
		link.textContent = item.title;
		results.appendChild( link );
	} );

	appendFeedbackHint( results );
}

function appendFeedbackHint( results: HTMLElement ) {
	const hint = document.createElement( 'p' );
	hint.className = 'itsdz-search-empty-hint';
	hint.textContent = __(
		'Still stuck? Open an article and use “Was this helpful?” so we know what to improve.',
		'itsmanzur-docs'
	);
	results.appendChild( hint );
}
