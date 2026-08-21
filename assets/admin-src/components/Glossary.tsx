import {
	Button,
	Card,
	CardBody,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store } from '../store';

interface GlossaryTerm {
	id: number;
	name: string;
	description: string;
	meta?: { itsdz_glossary_aliases?: string };
}

const EMPTY_DRAFT = { name: '', description: '', aliases: '' };

export function Glossary() {
	const { setNotice } = useDispatch( store );
	const [ terms, setTerms ] = useState< GlossaryTerm[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ busy, setBusy ] = useState( false );
	const [ draft, setDraft ] = useState( EMPTY_DRAFT );
	const [ editingId, setEditingId ] = useState< number | null >( null );

	const load = useCallback( async () => {
		setLoading( true );
		try {
			const items = await apiFetch< GlossaryTerm[] >( {
				path: '/wp/v2/itsdz_glossary?per_page=100&orderby=name&order=asc',
			} );
			setTerms( items );
		} catch {
			setTerms( [] );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		void load();
	}, [ load ] );

	const reset = () => {
		setDraft( EMPTY_DRAFT );
		setEditingId( null );
	};

	const save = async () => {
		if ( ! draft.name.trim() ) {
			return;
		}

		setBusy( true );
		try {
			await apiFetch( {
				path: editingId
					? `/wp/v2/itsdz_glossary/${ editingId }`
					: '/wp/v2/itsdz_glossary',
				method: 'POST',
				data: {
					name: draft.name.trim(),
					description: draft.description.trim(),
					meta: { itsdz_glossary_aliases: draft.aliases.trim() },
				},
			} );

			setNotice( {
				status: 'success',
				message: editingId
					? __( 'Glossary term updated.', 'itsmanzur-docs' )
					: __( 'Glossary term added.', 'itsmanzur-docs' ),
			} );
			reset();
			await load();
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'The glossary term could not be saved.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const remove = async ( term: GlossaryTerm ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( __( 'Delete this glossary term?', 'itsmanzur-docs' ) ) ) {
			return;
		}

		setBusy( true );
		try {
			await apiFetch( {
				path: `/wp/v2/itsdz_glossary/${ term.id }?force=true`,
				method: 'DELETE',
			} );
			setNotice( { status: 'success', message: __( 'Glossary term deleted.', 'itsmanzur-docs' ) } );

			if ( editingId === term.id ) {
				reset();
			}

			await load();
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'The glossary term could not be deleted.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const edit = ( term: GlossaryTerm ) => {
		setEditingId( term.id );
		setDraft( {
			name: term.name,
			description: term.description,
			aliases: term.meta?.itsdz_glossary_aliases ?? '',
		} );
	};

	return (
		<div className="itsdz-settings-container">
			<div className="itsdz-settings-header">
				<div>
					<h1>{ __( 'Glossary', 'itsmanzur-docs' ) }</h1>
					<p>
						{ __(
							'Define the terms your product uses. The first mention of each term in an article is highlighted with its definition.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
			</div>

			<div className="itsdz-settings-sections">
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-plus-alt" aria-hidden="true" />
							<h2>
								{ editingId
									? __( 'Edit term', 'itsmanzur-docs' )
									: __( 'Add a term', 'itsmanzur-docs' ) }
							</h2>
						</div>

						<TextControl
							label={ __( 'TERM', 'itsmanzur-docs' ) }
							value={ draft.name }
							onChange={ ( value: string ) => setDraft( { ...draft, name: value } ) }
							placeholder={ __( 'API key', 'itsmanzur-docs' ) }
						/>
						<TextareaControl
							label={ __( 'DEFINITION', 'itsmanzur-docs' ) }
							value={ draft.description }
							rows={ 3 }
							onChange={ ( value: string ) =>
								setDraft( { ...draft, description: value } )
							}
							placeholder={ __( 'A secret string used to authenticate requests.', 'itsmanzur-docs' ) }
						/>
						<TextControl
							label={ __( 'ALTERNATIVE SPELLINGS', 'itsmanzur-docs' ) }
							value={ draft.aliases }
							onChange={ ( value: string ) => setDraft( { ...draft, aliases: value } ) }
							placeholder={ __( 'API keys, api-key', 'itsmanzur-docs' ) }
							help={ __(
								'Comma separated. These are highlighted as well as the main term.',
								'itsmanzur-docs'
							) }
						/>

						<div className="itsdz-sample-actions">
							<Button
								variant="primary"
								onClick={ () => void save() }
								disabled={ busy || ! draft.name.trim() }
							>
								{ busy && <Spinner /> }{ ' ' }
								{ editingId
									? __( 'Update term', 'itsmanzur-docs' )
									: __( 'Add term', 'itsmanzur-docs' ) }
							</Button>
							{ editingId && (
								<Button variant="tertiary" onClick={ reset } disabled={ busy }>
									{ __( 'Cancel', 'itsmanzur-docs' ) }
								</Button>
							) }
						</div>
					</CardBody>
				</Card>

				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-book-alt" aria-hidden="true" />
							<h2>
								{ __( 'All terms', 'itsmanzur-docs' ) } ({ terms.length })
							</h2>
						</div>

						{ loading && <Spinner /> }

						{ ! loading && terms.length === 0 && (
							<p className="itsdz-settings-card-intro">
								{ __( 'No glossary terms yet. Add your first one above.', 'itsmanzur-docs' ) }
							</p>
						) }

						{ terms.map( ( term ) => (
							<div className="itsdz-glossary-row" key={ term.id }>
								<div>
									<strong>{ term.name }</strong>
									<p>{ term.description || __( '(no definition yet)', 'itsmanzur-docs' ) }</p>
									{ term.meta?.itsdz_glossary_aliases && (
										<span className="itsdz-glossary-row-aliases">
											{ __( 'Also:', 'itsmanzur-docs' ) }{ ' ' }
											{ term.meta.itsdz_glossary_aliases }
										</span>
									) }
								</div>
								<div className="itsdz-glossary-row-actions">
									<Button
										variant="tertiary"
										onClick={ () => edit( term ) }
										disabled={ busy }
									>
										{ __( 'Edit', 'itsmanzur-docs' ) }
									</Button>
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => void remove( term ) }
										disabled={ busy }
									>
										{ __( 'Delete', 'itsmanzur-docs' ) }
									</Button>
								</div>
							</div>
						) ) }
					</CardBody>
				</Card>
			</div>
		</div>
	);
}
