import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SHORTCODE } from './onboardingApi';

type Props = {
	busy: boolean;
	pageUrl: string;
	onBack: () => void;
	onCreatePage: () => void;
	onNext: () => void;
	onSkip: () => void;
};

export function ShortcodeStep( {
	busy,
	pageUrl,
	onBack,
	onCreatePage,
	onNext,
	onSkip,
}: Props ) {
	const [ copied, setCopied ] = useState( false );

	const copy = async () => {
		try {
			await navigator.clipboard.writeText( SHORTCODE );
			setCopied( true );
		} catch ( error ) {
			setCopied( false );
		}
	};

	return (
		<div className="itsdz-onboarding-step">
			<p className="itsdz-onboarding-kicker">
				{ __( 'Step 4 of 5', 'itsmanzur-docs' ) }
			</p>
			<h2 className="itsdz-onboarding-title">
				{ __( 'Put docs on any page', 'itsmanzur-docs' ) }
			</h2>
			<p className="itsdz-onboarding-lede">
				{ __(
					'Paste this shortcode into a page, post, or widget. Only published articles appear — drafts stay private.',
					'itsmanzur-docs'
				) }
			</p>
			<div className="itsdz-onboarding-code">
				<code>{ SHORTCODE }</code>
				<Button variant="primary" onClick={ () => void copy() }>
					{ copied
						? __( 'Copied', 'itsmanzur-docs' )
						: __( 'Copy', 'itsmanzur-docs' ) }
				</Button>
			</div>
			<button
				type="button"
				className="itsdz-onboarding-create-page"
				onClick={ onCreatePage }
				disabled={ busy }
			>
				<span
					className="dashicons dashicons-welcome-add-page"
					aria-hidden="true"
				/>
				<span>
					<strong>
						{ __(
							'Auto-create a page with this shortcode',
							'itsmanzur-docs'
						) }
					</strong>
					<span>
						{ __(
							'Publishes a Documentation page you can edit or unpublish later.',
							'itsmanzur-docs'
						) }
					</span>
				</span>
			</button>
			{ pageUrl ? (
				<p className="itsdz-onboarding-page-link">
					<a href={ pageUrl } target="_blank" rel="noreferrer">
						{ __( 'View the documentation page', 'itsmanzur-docs' ) }
					</a>
				</p>
			) : null }
			<div className="itsdz-onboarding-actions">
				<Button variant="tertiary" onClick={ onBack } disabled={ busy }>
					{ __( 'Back', 'itsmanzur-docs' ) }
				</Button>
				<div className="itsdz-onboarding-actions-end">
					<Button variant="tertiary" onClick={ onSkip } disabled={ busy }>
						{ __( 'Skip remaining steps', 'itsmanzur-docs' ) }
					</Button>
					<Button variant="primary" onClick={ onNext } disabled={ busy }>
						{ __( 'Continue', 'itsmanzur-docs' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
