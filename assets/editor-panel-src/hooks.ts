/**
 * Data fetching for the Doczur sidebar panel.
 *
 * A separate, minimal set of hooks rather than importing from admin-src or
 * blocks-src: each is its own webpack entry / bundle, so sharing code across
 * them would mean shipping admin-src's much larger dependency tree (dnd-kit,
 * the full DocsManager tree) into every post-editing screen.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export interface SelectOption {
	label: string;
	value: string;
}

interface KnowledgeBase {
	id: number;
	title: string;
}

export interface Term {
	id: number;
	name: string;
}

/**
 * Fetch Doczur projects for the "Knowledge base" dropdown.
 */
export function useKnowledgeBases(): SelectOption[] {
	const placeholder = __( 'Select a knowledge base…', 'doczur' );
	const [ options, setOptions ] = useState< SelectOption[] >( [
		{ label: placeholder, value: '0' },
	] );

	useEffect( () => {
		let cancelled = false;

		apiFetch< KnowledgeBase[] >( { path: '/itsdz/v1/kb' } )
			.then( ( items ) => {
				if ( cancelled ) {
					return;
				}
				setOptions( [
					{ label: placeholder, value: '0' },
					...items.map( ( kb ) => ( {
						label: kb.title || __( '(untitled)', 'doczur' ),
						value: String( kb.id ),
					} ) ),
				] );
			} )
			.catch( () => {
				// Leave the placeholder in place; the panel still lets the
				// rest of the fields be edited.
			} );

		return () => {
			cancelled = true;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return options;
}

/**
 * Fetch a taxonomy's terms as `SelectControl` options, with a "none" entry.
 * @param restRoute
 * @param noneLabel
 */
function useTermOptions(
	restRoute: string,
	noneLabel: string
): SelectOption[] {
	const [ options, setOptions ] = useState< SelectOption[] >( [
		{ label: noneLabel, value: '0' },
	] );

	useEffect( () => {
		let cancelled = false;

		apiFetch< Term[] >( {
			path: `${ restRoute }?per_page=100&orderby=name&order=asc`,
		} )
			.then( ( terms ) => {
				if ( cancelled ) {
					return;
				}
				setOptions( [
					{ label: noneLabel, value: '0' },
					...terms.map( ( term ) => ( {
						label: term.name,
						value: String( term.id ),
					} ) ),
				] );
			} )
			.catch( () => {} );

		return () => {
			cancelled = true;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return options;
}

export function useSections(): SelectOption[] {
	return useTermOptions(
		'/wp/v2/itsdz_section',
		__( 'Unsectioned', 'doczur' )
	);
}

/**
 * Version options, plus a creator for when the one you need doesn't exist
 * yet — versions are a single-select taxonomy, so unlike tags there is no
 * free-text field to type a new one into.
 */
export function useVersions(): {
	options: SelectOption[];
	createVersion: ( name: string ) => Promise< Term >;
} {
	const [ terms, setTerms ] = useState< Term[] >( [] );
	const noneLabel = __( 'No version', 'doczur' );

	useEffect( () => {
		let cancelled = false;

		apiFetch< Term[] >( {
			path: '/wp/v2/itsdz_version?per_page=100&orderby=name&order=asc',
		} )
			.then( ( items ) => {
				if ( ! cancelled ) {
					setTerms( items );
				}
			} )
			.catch( () => {} );

		return () => {
			cancelled = true;
		};
	}, [] );

	const createVersion = async ( name: string ): Promise< Term > => {
		const term = await apiFetch< Term >( {
			path: '/wp/v2/itsdz_version',
			method: 'POST',
			data: { name },
		} );
		setTerms( ( prev ) => [ ...prev, term ] );
		return term;
	};

	const options: SelectOption[] = [
		{ label: noneLabel, value: '0' },
		...terms.map( ( term ) => ( {
			label: term.name,
			value: String( term.id ),
		} ) ),
	];

	return { options, createVersion };
}

/**
 * Tag terms plus a resolver that turns typed names into term IDs, creating
 * any tag that does not exist yet — the same behaviour FormTokenField needs.
 */
export function useTags() {
	const [ terms, setTerms ] = useState< Term[] >( [] );

	const load = () => {
		apiFetch< Term[] >( {
			path: '/wp/v2/itsdz_tag?per_page=100&orderby=name&order=asc',
		} )
			.then( setTerms )
			.catch( () => {} );
	};

	useEffect( load, [] );

	/**
	 * Resolve a list of tag names to term IDs, creating unmatched ones.
	 * @param names
	 */
	const ensureTerms = async ( names: string[] ): Promise< number[] > => {
		const trimmed = names.map( ( name ) => name.trim() ).filter( Boolean );
		const known = new Map(
			terms.map( ( term ) => [ term.name.toLowerCase(), term ] )
		);
		const created: Term[] = [];

		const ids = await Promise.all(
			trimmed.map( async ( name ) => {
				const existing = known.get( name.toLowerCase() );
				if ( existing ) {
					return existing.id;
				}

				const term = await apiFetch< Term >( {
					path: '/wp/v2/itsdz_tag',
					method: 'POST',
					data: { name },
				} );
				created.push( term );
				known.set( term.name.toLowerCase(), term );
				return term.id;
			} )
		);

		if ( created.length ) {
			setTerms( ( prev ) => [ ...prev, ...created ] );
		}

		return ids;
	};

	return { terms, ensureTerms };
}
