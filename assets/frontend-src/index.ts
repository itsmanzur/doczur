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
