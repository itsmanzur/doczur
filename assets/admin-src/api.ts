import apiFetch from '@wordpress/api-fetch';
import type { Article, Project, Section } from './types';

apiFetch.use( apiFetch.createNonceMiddleware( window.itsdzAdmin.restNonce ) );
apiFetch.use( apiFetch.createRootURLMiddleware( window.itsdzAdmin.restRoot ) );

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
	listArticles: ( kbId: number ) =>
		apiFetch< Article[] >( {
			path: `/itsdz/v1/articles?kb_id=${ kbId }`,
		} ),
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
			path: '/wp/v2/itsdz_section?per_page=100&orderby=name&order=asc',
		} ),
	createSection: ( name: string, parent = 0 ) =>
		apiFetch< Section >( {
			path: '/wp/v2/itsdz_section',
			method: 'POST',
			data: { name, parent },
		} ),
	importArticles: ( kbId: number, articles: Record< string, unknown >[] ) =>
		apiFetch< { created: number; article_ids: number[] } >( {
			path: '/itsdz/v1/import',
			method: 'POST',
			data: { kb_id: kbId, articles },
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
		anchor.download = `doczur-export-${ kbId }.json`;
		anchor.click();
		URL.revokeObjectURL( url );
	},
};
