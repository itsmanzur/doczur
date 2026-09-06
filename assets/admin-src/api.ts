import apiFetch from '@wordpress/api-fetch';
import type { Article, Project, Section } from './types';

apiFetch.use( apiFetch.createNonceMiddleware( window.itsdzAdmin.restNonce ) );
apiFetch.use( apiFetch.createRootURLMiddleware( window.itsdzAdmin.restRoot ) );

type DoczurSettings = {
	delete_data_on_uninstall: boolean;
	show_powered_by: boolean;
};

export const api = {
	listProjects: () => apiFetch< Project[] >( { path: '/itsdz/v1/kb' } ),
	createProject: ( payload: Record< string, unknown > ) =>
		apiFetch< Project >( {
			path: '/itsdz/v1/kb',
			method: 'POST',
			data: payload,
		} ),
	updateProject: ( id: number, payload: Record< string, unknown > ) =>
		apiFetch< Project >( {
			path: `/itsdz/v1/kb/${ id }`,
			method: 'PUT',
			data: payload,
		} ),
	async listArticles( kbId: number ) {
		const perPage = 100;
		const articles: Article[] = [];
		let page = 1;
		let totalPages = 1;

		do {
			const response = await apiFetch< Response >( {
				path: `/itsdz/v1/articles?kb_id=${ kbId }&page=${ page }&per_page=${ perPage }`,
				parse: false,
			} );

			if ( ! response.ok ) {
				throw new Error( 'Articles could not be loaded.' );
			}

			const pagesHeader = response.headers.get( 'X-WP-TotalPages' );
			totalPages = Math.max( 1, Number.parseInt( pagesHeader || '1', 10 ) || 1 );
			articles.push( ...( ( await response.json() ) as Article[] ) );
			page += 1;
		} while ( page <= totalPages && page <= 50 );

		return articles;
	},
	createArticle: ( payload: Record< string, unknown > ) =>
		apiFetch< Article >( {
			path: '/itsdz/v1/articles',
			method: 'POST',
			data: payload,
		} ),
	updateArticle: ( id: number, payload: Record< string, unknown > ) =>
		apiFetch< Article >( {
			path: `/itsdz/v1/articles/${ id }`,
			method: 'PUT',
			data: payload,
		} ),
	deleteArticle: ( id: number ) =>
		apiFetch< { deleted: boolean } >( {
			path: `/itsdz/v1/articles/${ id }`,
			method: 'DELETE',
		} ),
	reorderArticles: ( kbId: number, articles: Article[] ) =>
		apiFetch< { updated: number } >( {
			path: '/itsdz/v1/articles/reorder',
			method: 'POST',
			data: {
				kb_id: kbId,
				items: articles.map( ( article, index ) => ( {
					id: article.id,
					menu_order: index,
					section_id: article.section_ids[ 0 ] ?? 0,
				} ) ),
			},
		} ),
	listSections: () =>
		apiFetch< Section[] >( {
			path: '/wp/v2/itsdz_section?per_page=100&orderby=name&order=asc&context=edit',
		} ),
	createSection: (
		name: string,
		parent = 0,
		extra: Record< string, unknown > = {}
	) =>
		apiFetch< Section >( {
			path: '/wp/v2/itsdz_section',
			method: 'POST',
			data: parent
				? { name, parent, ...extra }
				: { name, ...extra },
		} ),
	updateSection: ( id: number, data: Record< string, unknown > ) =>
		apiFetch< Section >( {
			path: `/wp/v2/itsdz_section/${ id }`,
			method: 'POST',
			data,
		} ),
	deleteSection: ( id: number ) =>
		apiFetch< { deleted: boolean } >( {
			path: `/wp/v2/itsdz_section/${ id }?force=true`,
			method: 'DELETE',
		} ),
	getSampleDataStatus: ( kbId: number ) =>
		apiFetch< { exists: boolean } >( {
			path: `/itsdz/v1/sample-data?kb_id=${ kbId }`,
		} ),
	createSampleData: ( kbId: number ) =>
		apiFetch< { articles: number; sections: number; exists: boolean } >( {
			path: '/itsdz/v1/sample-data',
			method: 'POST',
			data: { kb_id: kbId },
		} ),
	removeSampleData: ( kbId: number ) =>
		apiFetch< { articles: number; sections: number; exists: boolean } >( {
			path: `/itsdz/v1/sample-data?kb_id=${ kbId }`,
			method: 'DELETE',
		} ),
	importArticles: ( kbId: number, articles: Record< string, unknown >[] ) =>
		apiFetch< { created: number; article_ids: number[] } >( {
			path: '/itsdz/v1/import',
			method: 'POST',
			data: { kb_id: kbId, articles },
		} ),
	getSettings: () =>
		apiFetch< DoczurSettings >( {
			path: '/itsdz/v1/settings',
		} ),
	updateSettings: ( payload: Partial< DoczurSettings > ) =>
		apiFetch< DoczurSettings >( {
			path: '/itsdz/v1/settings',
			method: 'PUT',
			data: payload,
		} ),
	async downloadExport( kbId: number ) {
		const response = await fetch(
			`${ window.itsdzAdmin.restRoot }itsdz/v1/export?kb_id=${ kbId }`,
			{
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': window.itsdzAdmin.restNonce },
			}
		);

		if ( ! response.ok ) {
			throw new Error( 'Export failed.' );
		}

		const blob = await response.blob();
		const url = URL.createObjectURL( blob );
		const anchor = document.createElement( 'a' );
		anchor.href = url;
		anchor.download = `itsmanzur-docs-export-${ kbId }.json`;
		anchor.click();
		URL.revokeObjectURL( url );
	},
};
