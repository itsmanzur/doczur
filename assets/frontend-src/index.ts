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
}
