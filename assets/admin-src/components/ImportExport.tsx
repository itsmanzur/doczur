import { Button, Card, CardBody, Spinner } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import type { Project } from '../types';

export function ImportExport( { project }: { project: Project } ) {
	const { setNotice } = useDispatch( store );
	const [ busy, setBusy ] = useState( false );

	const exportJson = async () => {
		setBusy( true );
		try {
			await api.downloadExport( project.id );
			setNotice( {
				status: 'success',
				message: __( 'Export downloaded.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Export failed.', 'doczur' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const importJson = async ( file: File ) => {
		setBusy( true );
		try {
			const payload = JSON.parse( await file.text() ) as {
				articles?: Record< string, unknown >[];
			};
			if ( ! Array.isArray( payload.articles ) ) {
				throw new Error(
					__( 'This is not a valid Doczur export.', 'doczur' )
				);
			}
			const result = await api.importArticles(
				project.id,
				payload.articles
			);
			setNotice( {
				status: 'success',
				message: `${ result.created } ${ __(
					'articles imported as drafts.',
					'doczur'
				) }`,
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Import failed.', 'doczur' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className="itsdz-transfer-grid">
			<Card>
				<CardBody>
					<h1>{ __( 'Export documentation', 'doczur' ) }</h1>
					<p>
						{ __(
							'Download a portable JSON backup of the project, articles, sections, and tags.',
							'doczur'
						) }
					</p>
					<Button
						variant="primary"
						onClick={ () => void exportJson() }
						disabled={ busy }
					>
						{ busy && <Spinner /> }{ ' ' }
						{ __( 'Download JSON', 'doczur' ) }
					</Button>
				</CardBody>
			</Card>
			<Card>
				<CardBody>
					<h2>{ __( 'Import documentation', 'doczur' ) }</h2>
					<p>
						{ __(
							'Imported articles are always created as drafts so you can review them safely.',
							'doczur'
						) }
					</p>
					<label
						className="itsdz-file-button"
						htmlFor="itsdz-import-file"
					>
						<span>
							{ busy
								? __( 'Working…', 'doczur' )
								: __( 'Choose JSON file', 'doczur' ) }
						</span>
						<input
							id="itsdz-import-file"
							type="file"
							accept="application/json,.json"
							disabled={ busy }
							onChange={ ( event ) => {
								const file = event.target.files?.[ 0 ];
								if ( file ) {
									void importJson( file );
								}
							} }
						/>
					</label>
				</CardBody>
			</Card>
		</div>
	);
}
