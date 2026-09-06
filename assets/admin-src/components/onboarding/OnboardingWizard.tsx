import { Card, CardBody, Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../../api';
import {
	bodyFromMarkdown,
	markdownToHtml,
	titleFromMarkdown,
} from '../../markdownImport';
import type { Section } from '../../types';
import { CompleteStep } from './CompleteStep';
import { NavStyleStep } from './NavStyleStep';
import {
	completeOnboardingStep,
	getOnboardingStatus,
	restErrorMessage,
	skipOnboarding,
} from './onboardingApi';
import { SectionStep } from './SectionStep';
import { ShortcodeStep } from './ShortcodeStep';
import { WelcomeStep } from './WelcomeStep';

const SAMPLE_MARKDOWN = `# Welcome to your docs

This sample article was imported during setup. Edit or delete it at any time.

## What you can do next

- Add more articles from the Documentation screen
- Organize them into sections
- Paste \`[nirdeshio_docs]\` on any page
`;

const STEP_LABELS = [
	__( 'Welcome', 'itsmanzur-docs' ),
	__( 'Section', 'itsmanzur-docs' ),
	__( 'Navigation', 'itsmanzur-docs' ),
	__( 'Shortcode', 'itsmanzur-docs' ),
	__( 'Done', 'itsmanzur-docs' ),
];

type Props = {
	onComplete: () => Promise< void >;
	onSkip: () => Promise< void >;
};

function termIdFromRestError( caught: unknown ): number {
	if ( ! caught || typeof caught !== 'object' || ! ( 'data' in caught ) ) {
		return 0;
	}

	const data = ( caught as { data: unknown } ).data;

	if ( typeof data === 'number' ) {
		return data;
	}

	if ( data && typeof data === 'object' && 'term_id' in data ) {
		return absint( ( data as { term_id: unknown } ).term_id );
	}

	return 0;
}

function absint( value: unknown ): number {
	const parsed = Number( value );

	return Number.isFinite( parsed ) && parsed > 0 ? Math.floor( parsed ) : 0;
}

function stepClassName( number: number, current: number ) {
	if ( number === current ) {
		return 'is-current';
	}

	return number < current ? 'is-done' : '';
}

export default function OnboardingWizard( { onComplete, onSkip }: Props ) {
	const [ step, setStep ] = useState( 1 );
	const [ analyticsOptIn, setAnalyticsOptIn ] = useState( false );
	const [ sectionName, setSectionName ] = useState(
		__( 'Getting Started', 'itsmanzur-docs' )
	);
	const [ importSample, setImportSample ] = useState( true );
	const [ navStyle, setNavStyle ] = useState< 'accordion' | 'rail' | 'tree' >(
		'accordion'
	);
	const [ projectId, setProjectId ] = useState( 0 );
	const [ pageUrl, setPageUrl ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ ready, setReady ] = useState( false );

	useEffect( () => {
		void ( async () => {
			try {
				const status = await getOnboardingStatus();
				setAnalyticsOptIn( status.analytics_opt_in );
				setPageUrl( status.page_url );

				if ( 'in_progress' === status.status && status.step < 5 ) {
					setStep( Math.min( status.step + 1, 5 ) );
				}
			} catch ( caught ) {
				setError(
					restErrorMessage(
						caught,
						__(
							'The setup wizard could not load.',
							'itsmanzur-docs'
						)
					)
				);
			} finally {
				setReady( true );
			}
		} )();
	}, [] );

	const fail = ( caught: unknown ) => {
		setError(
			restErrorMessage(
				caught,
				__( 'Setup could not continue.', 'itsmanzur-docs' )
			)
		);
	};

	const persistStep = async (
		finished: number,
		extra: Record< string, unknown > = {}
	) => {
		const status = await completeOnboardingStep( {
			step: finished,
			analytics_opt_in: analyticsOptIn,
			...extra,
		} );
		setPageUrl( status.page_url );

		return status;
	};

	const handleSkip = async () => {
		setBusy( true );
		setError( '' );

		try {
			await skipOnboarding( analyticsOptIn );
			await onSkip();
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const handleStart = async () => {
		setBusy( true );
		setError( '' );

		try {
			await persistStep( 1 );
			setStep( 2 );
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const ensureProject = async (): Promise< number > => {
		if ( projectId ) {
			return projectId;
		}

		const projects = await api.listProjects();

		if ( projects.length ) {
			setProjectId( projects[ 0 ].id );
			const current =
				( projects[ 0 ].meta._itsdz_kb_nav_style as
					| 'accordion'
					| 'rail'
					| 'tree' ) || 'accordion';
			setNavStyle(
				[ 'accordion', 'rail', 'tree' ].includes( current )
					? current
					: 'accordion'
			);

			return projects[ 0 ].id;
		}

		const created = await api.createProject( {
			title: window.itsdzAdmin.siteName || __( 'Documentation', 'itsmanzur-docs' ),
			status: 'publish',
			meta: {
				_itsdz_kb_layout_mode: 'canvas',
				_itsdz_kb_nav_style: navStyle,
			},
		} );
		setProjectId( created.id );

		return created.id;
	};

	const ensureSection = async ( name: string ): Promise< Section > => {
		const existing = await api.listSections();
		const match = existing.find(
			( section ) =>
				section.name.toLowerCase() === name.toLowerCase() &&
				! section.parent
		);

		if ( match ) {
			return match;
		}

		try {
			return await api.createSection( name );
		} catch ( caught ) {
			const termId = termIdFromRestError( caught );

			if ( termId ) {
				const listed = await api.listSections();
				const found = listed.find( ( section ) => section.id === termId );

				if ( found ) {
					return found;
				}
			}

			throw caught;
		}
	};

	const handleSection = async () => {
		setBusy( true );
		setError( '' );

		try {
			const id = await ensureProject();
			const section = await ensureSection( sectionName.trim() );

			if ( importSample ) {
				const title = titleFromMarkdown(
					SAMPLE_MARKDOWN,
					__( 'Welcome to your docs', 'itsmanzur-docs' )
				);
				const articles = await api.listArticles( id );
				const already = articles.some(
					( article ) =>
						article.title === title &&
						article.section_ids.includes( section.id )
				);

				if ( ! already ) {
					await api.createArticle( {
						kb_id: id,
						title,
						content: markdownToHtml(
							bodyFromMarkdown( SAMPLE_MARKDOWN )
						),
						status: 'publish',
						section_ids: [ section.id ],
						menu_order: articles.length,
					} );
				}
			}

			await persistStep( 2 );
			setStep( 3 );
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const handleNav = async () => {
		setBusy( true );
		setError( '' );

		try {
			const id = await ensureProject();
			await api.updateProject( id, {
				meta: { _itsdz_kb_nav_style: navStyle },
			} );
			await persistStep( 3 );
			setStep( 4 );
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const handleCreatePage = async () => {
		setBusy( true );
		setError( '' );

		try {
			const status = await persistStep( 4, { create_page: true } );
			setPageUrl( status.page_url );
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const handleShortcodeNext = async () => {
		setBusy( true );
		setError( '' );

		try {
			await persistStep( 4 );
			setStep( 5 );
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	const handleFinish = async () => {
		setBusy( true );
		setError( '' );

		try {
			await persistStep( 5 );
			await onComplete();
		} catch ( caught ) {
			fail( caught );
		} finally {
			setBusy( false );
		}
	};

	if ( ! ready ) {
		return (
			<div className="itsdz-loading" role="status">
				<Spinner />
				<span>{ __( 'Loading setup…', 'itsmanzur-docs' ) }</span>
			</div>
		);
	}

	return (
		<div className="itsdz-wizard itsdz-onboarding">
			<div className="itsdz-onboarding-head">
				<span className="itsdz-brand-mark" aria-hidden="true">
					N
				</span>
				<div>
					<p className="itsdz-onboarding-kicker">
						{ __( 'Nirdeshio setup', 'itsmanzur-docs' ) }
					</p>
					<h1>{ __( 'Set up Nirdeshio', 'itsmanzur-docs' ) }</h1>
				</div>
			</div>
			<ol className="itsdz-stepper" aria-label={ __( 'Setup steps', 'itsmanzur-docs' ) }>
				{ STEP_LABELS.map( ( label, index ) => (
					<li key={ label } className={ stepClassName( index + 1, step ) }>
						<span>{ index + 1 < step ? '✓' : index + 1 }</span>
						{ label }
					</li>
				) ) }
			</ol>
			<Card className="itsdz-wizard-card itsdz-onboarding-card">
				<CardBody>
					{ error ? (
						<Notice status="error" onRemove={ () => setError( '' ) }>
							{ error }
						</Notice>
					) : null }
					{ 1 === step && (
						<WelcomeStep
							analyticsOptIn={ analyticsOptIn }
							busy={ busy }
							onAnalyticsChange={ setAnalyticsOptIn }
							onSkip={ () => void handleSkip() }
							onStart={ () => void handleStart() }
						/>
					) }
					{ 2 === step && (
						<SectionStep
							busy={ busy }
							importSample={ importSample }
							sectionName={ sectionName }
							onBack={ () => setStep( 1 ) }
							onImportChange={ setImportSample }
							onNameChange={ setSectionName }
							onNext={ () => void handleSection() }
							onSkip={ () => void handleSkip() }
						/>
					) }
					{ 3 === step && (
						<NavStyleStep
							busy={ busy }
							navStyle={ navStyle }
							onBack={ () => setStep( 2 ) }
							onNext={ () => void handleNav() }
							onSkip={ () => void handleSkip() }
							onStyleChange={ setNavStyle }
						/>
					) }
					{ 4 === step && (
						<ShortcodeStep
							busy={ busy }
							pageUrl={ pageUrl }
							onBack={ () => setStep( 3 ) }
							onCreatePage={ () => void handleCreatePage() }
							onNext={ () => void handleShortcodeNext() }
							onSkip={ () => void handleSkip() }
						/>
					) }
					{ 5 === step && (
						<CompleteStep
							busy={ busy }
							pageUrl={ pageUrl }
							onFinish={ () => void handleFinish() }
						/>
					) }
				</CardBody>
			</Card>
			{ busy ? (
				<div className="itsdz-onboarding-busy" role="status">
					<Spinner />
				</div>
			) : null }
		</div>
	);
}
