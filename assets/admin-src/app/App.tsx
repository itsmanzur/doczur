import { Button, Notice, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import { DocsManager } from '../components/DocsManager';
import { ImportExport } from '../components/ImportExport';
import { Settings } from '../components/Settings';
import { SetupWizard } from '../components/SetupWizard';

type View = 'docs' | 'settings' | 'transfer' | 'wizard';

const viewFromHash = (): View => {
	const hash = window.location.hash.replace( '#/', '' );
	return [ 'docs', 'settings', 'transfer', 'wizard' ].includes( hash )
		? ( hash as View )
		: 'docs';
};

export function App() {
	const [ view, setView ] = useState< View >( viewFromHash );
	const { projects, loading, notice, selectedProjectId } = useSelect(
		( select ) => ( {
			projects: select( store ).getProjects(),
			loading: select( store ).isLoading(),
			notice: select( store ).getNotice() as {
				status: 'success' | 'error';
				message: string;
			} | null,
			selectedProjectId: select( store ).getSelectedProjectId(),
		} ),
		[]
	);
	const {
		setProjects,
		setArticles,
		setSections,
		selectProject,
		setLoading,
		setNotice,
	} = useDispatch( store );

	const loadWorkspace = useCallback( async () => {
		setLoading( true );
		try {
			const loadedProjects = await api.listProjects();
			setProjects( loadedProjects );

			if ( loadedProjects.length ) {
				const projectId = selectedProjectId ?? loadedProjects[ 0 ].id;
				selectProject( projectId );
				const [ articles, sections ] = await Promise.all( [
					api.listArticles( projectId ),
					api.listSections(),
				] );
				setArticles( articles );
				setSections( sections );
			} else {
				setView( 'wizard' );
			}
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __(
								'Doczur could not load the workspace.',
								'doczur'
						  ),
			} );
		} finally {
			setLoading( false );
		}
	}, [
		selectProject,
		selectedProjectId,
		setArticles,
		setLoading,
		setNotice,
		setProjects,
		setSections,
	] );

	useEffect( () => {
		void loadWorkspace();
	}, [ loadWorkspace ] );

	useEffect( () => {
		const handleHashChange = () => setView( viewFromHash() );
		window.addEventListener( 'hashchange', handleHashChange );
		return () =>
			window.removeEventListener( 'hashchange', handleHashChange );
	}, [] );

	const navigate = ( nextView: View ) => {
		window.location.hash = `/${ nextView }`;
		setView( nextView );
	};

	if ( loading ) {
		return (
			<div className="itsdz-loading" role="status">
				<Spinner />
				<span>{ __( 'Loading Doczur…', 'doczur' ) }</span>
			</div>
		);
	}

	if ( ! projects.length || view === 'wizard' ) {
		return (
			<SetupWizard
				onComplete={ async () => {
					await loadWorkspace();
					navigate( 'docs' );
				} }
				onCancel={
					projects.length ? () => navigate( 'docs' ) : undefined
				}
			/>
		);
	}

	const selectedProject =
		projects.find( ( project ) => project.id === selectedProjectId ) ??
		projects[ 0 ];

	return (
		<div className="itsdz-app-shell">
			<header className="itsdz-topbar">
				<div>
					<span className="itsdz-brand-mark" aria-hidden="true">
						D
					</span>
					<strong>Doczur</strong>
					<span className="itsdz-version">
						v{ window.itsdzAdmin.pluginVersion }
					</span>
				</div>
				<div className="itsdz-user">
					{ window.itsdzAdmin.user.displayName }
				</div>
			</header>

			<div className="itsdz-workspace">
				<nav
					className="itsdz-app-nav"
					aria-label={ __( 'Doczur navigation', 'doczur' ) }
				>
					{ (
						[
							[ 'docs', __( 'Documentation', 'doczur' ) ],
							[ 'settings', __( 'Settings', 'doczur' ) ],
							[ 'transfer', __( 'Import / Export', 'doczur' ) ],
						] as [ View, string ][]
					 ).map( ( [ itemView, label ] ) => (
						<Button
							key={ itemView }
							className={ view === itemView ? 'is-active' : '' }
							onClick={ () => navigate( itemView ) }
							aria-current={
								view === itemView ? 'page' : undefined
							}
						>
							{ label }
						</Button>
					) ) }
					<Button disabled className="itsdz-pro-nav">
						{ __( 'Analytics', 'doczur' ) } <small>PRO</small>
					</Button>
				</nav>

				<main className="itsdz-main">
					{ notice && (
						<Notice
							status={ notice.status }
							onRemove={ () => setNotice( null ) }
						>
							{ notice.message }
						</Notice>
					) }
					{ view === 'docs' && (
						<DocsManager project={ selectedProject } />
					) }
					{ view === 'settings' && (
						<Settings project={ selectedProject } />
					) }
					{ view === 'transfer' && (
						<ImportExport project={ selectedProject } />
					) }
				</main>
			</div>
		</div>
	);
}
