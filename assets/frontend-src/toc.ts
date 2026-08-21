import { __ } from '@wordpress/i18n';

export function initToc( root: HTMLElement ) {
	const content = root.querySelector< HTMLElement >( '[data-itsdz-content]' );
	const toc = root.querySelector< HTMLElement >( '[data-itsdz-toc]' );

	if ( ! content || ! toc ) {
		return;
	}

	const headings = Array.from(
		content.querySelectorAll< HTMLElement >( 'h2, h3' )
	);

	if ( ! headings.length ) {
		toc.parentElement?.setAttribute( 'hidden', '' );
		return;
	}

	const usedIds = new Set< string >();
	headings.forEach( ( heading, index ) => {
		let id = heading.id || `section-${ index + 1 }`;
		while ( usedIds.has( id ) ) {
			id = `${ id }-${ index + 1 }`;
		}
		heading.id = id;
		usedIds.add( id );

		const link = document.createElement( 'a' );
		link.href = `#${ encodeURIComponent( id ) }`;
		link.textContent =
			heading.textContent || __( 'Untitled section', 'itsmanzur-docs' );
		link.dataset.level = heading.tagName.toLowerCase();
		toc.appendChild( link );
	} );

	if ( 'IntersectionObserver' in window ) {
		const links = Array.from(
			toc.querySelectorAll< HTMLAnchorElement >( 'a' )
		);
		const observer = new IntersectionObserver(
			( entries ) => {
				const visible = entries.find(
					( entry ) => entry.isIntersecting
				);
				if ( ! visible ) {
					return;
				}
				links.forEach( ( link ) => {
					link.classList.toggle(
						'is-active',
						link.hash === `#${ visible.target.id }`
					);
				} );
			},
			{ rootMargin: '-15% 0px -75% 0px' }
		);
		headings.forEach( ( heading ) => observer.observe( heading ) );
	}
}
