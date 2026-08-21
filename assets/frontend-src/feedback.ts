import { __ } from '@wordpress/i18n';

export function initFeedback( root: HTMLElement ) {
	const feedback = root.querySelector< HTMLElement >(
		'[data-itsdz-feedback]'
	);
	const restUrl = root.dataset.restUrl;

	if ( ! feedback || ! restUrl ) {
		return;
	}

	const status = feedback.querySelector< HTMLElement >(
		'[data-itsdz-feedback-status]'
	);
	const buttons =
		feedback.querySelectorAll< HTMLButtonElement >( '[data-helpful]' );

	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			buttons.forEach( ( item ) => ( item.disabled = true ) );
			if ( status ) {
				status.textContent = __( 'Sending feedback…', 'itsmanzur-docs' );
			}

			try {
				const response = await fetch( `${ restUrl }feedback`, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( {
						article_id: Number( feedback.dataset.articleId ),
						helpful: button.dataset.helpful === 'true',
					} ),
				} );

				if ( ! response.ok ) {
					throw new Error();
				}

				if ( status ) {
					status.textContent = __(
						'Thank you for your feedback.',
						'itsmanzur-docs'
					);
				}
			} catch {
				if ( status ) {
					status.textContent = __(
						'Feedback could not be sent. Please try again.',
						'itsmanzur-docs'
					);
				}
				buttons.forEach( ( item ) => ( item.disabled = false ) );
			}
		} );
	} );
}
