import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type Props = {
	busy: boolean;
	pageUrl: string;
	onFinish: () => void;
};

export function CompleteStep( { busy, pageUrl, onFinish }: Props ) {
	return (
		<div className="itsdz-onboarding-step itsdz-onboarding-complete">
			<div className="itsdz-onboarding-complete-mark" aria-hidden="true">
				<span className="dashicons dashicons-yes" />
			</div>
			<p className="itsdz-onboarding-kicker">
				{ __( 'You are ready', 'itsmanzur-docs' ) }
			</p>
			<h2 className="itsdz-onboarding-title">
				{ __( 'Setup complete', 'itsmanzur-docs' ) }
			</h2>
			<p className="itsdz-onboarding-pitch">
				{ __(
					'Your first section is live in the dashboard. Keep writing — or open the public page if you created one.',
					'itsmanzur-docs'
				) }
			</p>
			{ pageUrl ? (
				<p className="itsdz-onboarding-page-link">
					<a href={ pageUrl } target="_blank" rel="noreferrer">
						{ __( 'Open the public documentation page', 'itsmanzur-docs' ) }
					</a>
				</p>
			) : null }
			<div className="itsdz-onboarding-actions itsdz-onboarding-actions--center">
				<Button variant="primary" onClick={ onFinish } disabled={ busy }>
					{ __( 'Go to dashboard', 'itsmanzur-docs' ) }
				</Button>
			</div>
		</div>
	);
}
