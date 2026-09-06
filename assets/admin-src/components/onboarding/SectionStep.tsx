import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type Props = {
	busy: boolean;
	importSample: boolean;
	sectionName: string;
	onBack: () => void;
	onImportChange: ( value: boolean ) => void;
	onNameChange: ( value: string ) => void;
	onNext: () => void;
	onSkip: () => void;
};

export function SectionStep( {
	busy,
	importSample,
	sectionName,
	onBack,
	onImportChange,
	onNameChange,
	onNext,
	onSkip,
}: Props ) {
	const previewName =
		sectionName.trim() || __( 'Getting Started', 'itsmanzur-docs' );

	return (
		<div className="itsdz-onboarding-step itsdz-onboarding-split">
			<div>
				<p className="itsdz-onboarding-kicker">
					{ __( 'Step 2 of 5', 'itsmanzur-docs' ) }
				</p>
				<h2 className="itsdz-onboarding-title">
					{ __( 'Name your first section', 'itsmanzur-docs' ) }
				</h2>
				<p className="itsdz-onboarding-lede">
					{ __(
						'Sections keep articles grouped in the public sidebar. You can add more later.',
						'itsmanzur-docs'
					) }
				</p>
				<TextControl
					label={ __( 'Section name', 'itsmanzur-docs' ) }
					value={ sectionName }
					onChange={ onNameChange }
					placeholder={ __( 'Getting Started', 'itsmanzur-docs' ) }
				/>
				<div
					className={ `itsdz-onboarding-sample ${
						importSample ? 'is-on' : ''
					}` }
				>
					<CheckboxControl
						label={ __( 'Import a sample article', 'itsmanzur-docs' ) }
						help={ __(
							'Uses the same Markdown import as Import / Export, then publishes it so the dashboard is not empty.',
							'itsmanzur-docs'
						) }
						checked={ importSample }
						onChange={ onImportChange }
					/>
				</div>
			</div>
			<div
				className="itsdz-onboarding-section-preview"
				aria-hidden="true"
			>
				<div className="itsdz-onboarding-section-preview-bar">
					<span className="itsdz-brand-mark">N</span>
					<strong>Nirdeshio</strong>
				</div>
				<div className="itsdz-onboarding-section-preview-body">
					<aside>
						<small>{ __( 'Sections', 'itsmanzur-docs' ) }</small>
						<strong>{ previewName }</strong>
						{ importSample ? (
							<em>
								{ __( 'Welcome to your docs', 'itsmanzur-docs' ) }
							</em>
						) : (
							<em className="is-empty">
								{ __( 'No articles yet', 'itsmanzur-docs' ) }
							</em>
						) }
					</aside>
					<main>
						<p className="itsdz-onboarding-section-preview-title">
							{ previewName }
						</p>
						<span className="itsdz-onboarding-skeleton" />
						<span className="itsdz-onboarding-skeleton" />
						<span className="itsdz-onboarding-skeleton is-short" />
					</main>
				</div>
			</div>
			<div className="itsdz-onboarding-actions">
				<Button variant="tertiary" onClick={ onBack } disabled={ busy }>
					{ __( 'Back', 'itsmanzur-docs' ) }
				</Button>
				<div className="itsdz-onboarding-actions-end">
					<Button variant="tertiary" onClick={ onSkip } disabled={ busy }>
						{ __( 'Skip remaining steps', 'itsmanzur-docs' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ onNext }
						disabled={ busy || ! sectionName.trim() }
					>
						{ __( 'Continue', 'itsmanzur-docs' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
