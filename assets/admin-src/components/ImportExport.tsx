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
				message: __( 'Export downloaded successfully.', 'itsmanzur-docs' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Export failed.', 'itsmanzur-docs' ),
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
					__( 'This is not a valid Nirdeshio export file.', 'itsmanzur-docs' )
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
					'itsmanzur-docs'
				) }`,
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Import failed.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className="itsdz-transfer-container">
			<div className="itsdz-settings-header">
				<div>
					<h1>{ __( 'Import & Export Documentation', 'itsmanzur-docs' ) }</h1>
					<p>{ __( 'Backup your documentation project or restore content from a JSON export.', 'itsmanzur-docs' ) }</p>
				</div>
			</div>

			<div className="itsdz-transfer-grid">
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-download" aria-hidden="true" />
							<h2>{ __( 'Export Documentation', 'itsmanzur-docs' ) }</h2>
						</div>
						<p className="itsdz-transfer-desc">
							{ __(
								'Download a portable JSON backup containing all articles, sections, tags, and structure.',
								'itsmanzur-docs'
							) }
						</p>
						<div className="itsdz-transfer-action">
							<Button
								variant="primary"
								onClick={ () => void exportJson() }
								disabled={ busy }
							>
								{ busy && <Spinner /> }{ ' ' }
								<span className="dashicons dashicons-download" aria-hidden="true" style={ { marginInlineEnd: '6px' } } />
								{ __( 'Download JSON Backup', 'itsmanzur-docs' ) }
							</Button>
						</div>
					</CardBody>
				</Card>

				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-upload" aria-hidden="true" />
							<h2>{ __( 'Import Documentation', 'itsmanzur-docs' ) }</h2>
						</div>
						<p className="itsdz-transfer-desc">
							{ __(
								'Restore or merge articles into this project. Imported articles are created as drafts for safety.',
								'itsmanzur-docs'
							) }
						</p>
						<div className="itsdz-transfer-action">
							<label
								className="itsdz-file-button"
								htmlFor="itsdz-import-file"
							>
								<span className="dashicons dashicons-upload" aria-hidden="true" style={ { marginInlineEnd: '6px' } } />
								<span>
									{ busy
										? __( 'Importing…', 'itsmanzur-docs' )
										: __( 'Choose JSON File', 'itsmanzur-docs' ) }
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
						</div>
					</CardBody>
				</Card>
			</div>
		</div>
	);
}
