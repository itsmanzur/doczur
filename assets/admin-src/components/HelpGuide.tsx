import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { Article, Project } from '../types';

type GuideDestination = 'docs' | 'settings' | 'transfer';

interface Props {
	project: Project;
	articles: Article[];
	onNavigate: ( destination: GuideDestination ) => void;
}

interface Lesson {
	id: string;
	number: string;
	title: string;
	summary: string;
	heading: string;
	description: string;
	steps: string[];
	action: string;
	destination: GuideDestination;
}

export function HelpGuide( { project, articles, onNavigate }: Props ) {
	const publishedArticles = articles.filter(
		( article ) => article.status === 'publish'
	).length;
	const initialLesson = articles.length ? 'write' : 'structure';
	const [ activeLesson, setActiveLesson ] = useState( initialLesson );
	const checklist = [
		{
			label: __( 'Documentation portal created', 'doczur' ),
			done: true,
		},
		{
			label: __( 'First article added', 'doczur' ),
			done: articles.length > 0,
		},
		{
			label: __( 'At least one article published', 'doczur' ),
			done: publishedArticles > 0,
		},
		{
			label: __( 'Help center ready to share', 'doczur' ),
			done: project.status === 'publish' && publishedArticles > 0,
		},
	];
	const completed = checklist.filter( ( item ) => item.done ).length;
	const progress = Math.round( ( completed / checklist.length ) * 100 );
	const isReady = progress === 100;
	const lessons: Lesson[] = [
		{
			id: 'structure',
			number: '01',
			title: __( 'Understand the basics', 'doczur' ),
			summary: __( 'Learn the three simple building blocks.', 'doczur' ),
			heading: __(
				'Think of Doczur as an organized help center',
				'doczur'
			),
			description: __(
				'Your documentation project is the whole help center. Sections work like folders, and articles are the helpful answers inside those folders.',
				'doczur'
			),
			steps: [
				__( 'Project: the complete documentation website.', 'doczur' ),
				__( 'Section: a group such as Installation or FAQ.', 'doczur' ),
				__(
					'Article: one clear answer to one customer question.',
					'doczur'
				),
			],
			action: __( 'Open documentation builder', 'doczur' ),
			destination: 'docs',
		},
		{
			id: 'write',
			number: '02',
			title: __( 'Write and publish', 'doczur' ),
			summary: __(
				'Create a useful article without technical work.',
				'doczur'
			),
			heading: __(
				'Start with the question customers ask most',
				'doczur'
			),
			description: __(
				'Give the article a clear title, write short step-by-step instructions, choose a section, and publish it when it is ready.',
				'doczur'
			),
			steps: [
				__(
					'Click Add article in the Documentation screen.',
					'doczur'
				),
				__(
					'Use a title that matches the customer question.',
					'doczur'
				),
				__(
					'Save as Draft while editing, then choose Published.',
					'doczur'
				),
			],
			action: __( 'Write an article', 'doczur' ),
			destination: 'docs',
		},
		{
			id: 'design',
			number: '03',
			title: __( 'Make it yours', 'doczur' ),
			summary: __( 'Match the help center to your brand.', 'doczur' ),
			heading: __(
				'Choose the look—Doczur handles the layout',
				'doczur'
			),
			description: __(
				'Add your project name, URL, brand color, color mode, and template. You can use the clean standalone canvas or keep your WordPress theme around the content.',
				'doczur'
			),
			steps: [
				__(
					'Open Settings and confirm the project name and URL.',
					'doczur'
				),
				__( 'Choose a brand color with good text contrast.', 'doczur' ),
				__( 'Save, then preview the visitor-facing page.', 'doczur' ),
			],
			action: __( 'Customize the design', 'doczur' ),
			destination: 'settings',
		},
		{
			id: 'maintain',
			number: '04',
			title: __( 'Share and maintain', 'doczur' ),
			summary: __( 'Keep documentation useful over time.', 'doczur' ),
			heading: __(
				'Share one link and improve answers as you learn',
				'doczur'
			),
			description: __(
				'Send customers to your documentation home page. Update articles when your product changes, review feedback, and download a JSON backup before large edits.',
				'doczur'
			),
			steps: [
				__(
					'Copy the live documentation URL and add it to your menu.',
					'doczur'
				),
				__(
					'Use helpful feedback to improve unclear articles.',
					'doczur'
				),
				__(
					'Download an export before major content changes.',
					'doczur'
				),
			],
			action: __( 'Open Import / Export', 'doczur' ),
			destination: 'transfer',
		},
	];
	const selectedLesson =
		lessons.find( ( lesson ) => lesson.id === activeLesson ) ??
		lessons[ 0 ];

	return (
		<div className="itsdz-help-guide">
			<section
				className="itsdz-guide-hero"
				aria-labelledby="itsdz-guide-title"
			>
				<div className="itsdz-guide-hero-copy">
					<span className="itsdz-guide-kicker">
						{ __( 'Welcome to Doczur', 'doczur' ) }
					</span>
					<h1 id="itsdz-guide-title">
						{ __(
							'Documentation made simple—for you and your customers.',
							'doczur'
						) }
					</h1>
					<p>
						{ __(
							'Doczur turns your WordPress site into a clean, searchable help center. You organize answers, publish them, and share one easy link. No coding is required.',
							'doczur'
						) }
					</p>
					<div className="itsdz-guide-actions">
						<Button
							variant="primary"
							onClick={ () => onNavigate( 'docs' ) }
						>
							{ isReady
								? __( 'Manage documentation', 'doczur' )
								: __( 'Continue building', 'doczur' ) }
						</Button>
						{ project.url && (
							<Button
								variant="secondary"
								href={ project.url }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'View live documentation', 'doczur' ) }
							</Button>
						) }
					</div>
					<ul
						className="itsdz-guide-benefits"
						aria-label={ __( 'Doczur benefits', 'doczur' ) }
					>
						<li>{ __( 'No coding required', 'doczur' ) }</li>
						<li>{ __( 'Fast visitor search', 'doczur' ) }</li>
						<li>{ __( 'Mobile friendly', 'doczur' ) }</li>
					</ul>
				</div>

				<aside
					className="itsdz-guide-progress"
					aria-label={ __( 'Launch progress', 'doczur' ) }
				>
					<div className="itsdz-progress-heading">
						<div>
							<span>
								{ __( 'Your launch progress', 'doczur' ) }
							</span>
							<strong>{ progress }%</strong>
						</div>
						<span
							className="itsdz-progress-ring"
							style={
								{
									'--itsdz-progress': `${
										progress * 3.6
									}deg`,
								} as React.CSSProperties
							}
							aria-hidden="true"
						>
							<span>{ completed }/4</span>
						</span>
					</div>
					{ isReady && (
						<p className="itsdz-progress-success" role="status">
							{ __(
								'Your help center is ready to share!',
								'doczur'
							) }
						</p>
					) }
					<ul>
						{ checklist.map( ( item ) => (
							<li
								className={ item.done ? 'is-done' : '' }
								key={ item.label }
							>
								<span aria-hidden="true">
									{ item.done ? '✓' : '·' }
								</span>
								{ item.label }
							</li>
						) ) }
					</ul>
				</aside>
			</section>

			<section
				className="itsdz-guide-model"
				aria-labelledby="itsdz-model-title"
			>
				<div className="itsdz-guide-section-heading">
					<span>{ __( 'The simple idea', 'doczur' ) }</span>
					<h2 id="itsdz-model-title">
						{ __( 'How Doczur is organized', 'doczur' ) }
					</h2>
					<p>
						{ __(
							'Three building blocks are all you need to remember.',
							'doczur'
						) }
					</p>
				</div>
				<div className="itsdz-model-grid">
					{ [
						[
							'1',
							__( 'Project', 'doczur' ),
							__(
								'Your complete help center—for example, “Acme App Help”.',
								'doczur'
							),
						],
						[
							'2',
							__( 'Section', 'doczur' ),
							__(
								'A folder that groups similar answers, such as Getting Started.',
								'doczur'
							),
						],
						[
							'3',
							__( 'Article', 'doczur' ),
							__(
								'One useful answer, such as “How to install the app”.',
								'doczur'
							),
						],
					].map( ( [ number, title, description ] ) => (
						<article key={ number }>
							<span>{ number }</span>
							<div>
								<h3>{ title }</h3>
								<p>{ description }</p>
							</div>
						</article>
					) ) }
				</div>
			</section>

			<section
				className="itsdz-guide-lessons"
				aria-labelledby="itsdz-lessons-title"
			>
				<div className="itsdz-guide-section-heading">
					<span>{ __( 'Guided walkthrough', 'doczur' ) }</span>
					<h2 id="itsdz-lessons-title">
						{ __( 'Choose what you want to do', 'doczur' ) }
					</h2>
					<p>
						{ __(
							'Select a step to see clear instructions and the right next action.',
							'doczur'
						) }
					</p>
				</div>
				<div className="itsdz-lesson-layout">
					<div
						className="itsdz-lesson-tabs"
						role="tablist"
						aria-label={ __( 'Doczur lessons', 'doczur' ) }
					>
						{ lessons.map( ( lesson ) => (
							<button
								type="button"
								role="tab"
								id={ `itsdz-lesson-tab-${ lesson.id }` }
								aria-controls="itsdz-lesson-panel"
								aria-selected={ activeLesson === lesson.id }
								className={
									activeLesson === lesson.id
										? 'is-active'
										: ''
								}
								onClick={ () => setActiveLesson( lesson.id ) }
								key={ lesson.id }
							>
								<span>{ lesson.number }</span>
								<div>
									<strong>{ lesson.title }</strong>
									<small>{ lesson.summary }</small>
								</div>
							</button>
						) ) }
					</div>
					<article
						className="itsdz-lesson-panel"
						id="itsdz-lesson-panel"
						role="tabpanel"
						aria-labelledby={ `itsdz-lesson-tab-${ selectedLesson.id }` }
					>
						<span className="itsdz-lesson-number">
							{ selectedLesson.number }
						</span>
						<h3>{ selectedLesson.heading }</h3>
						<p>{ selectedLesson.description }</p>
						<ol>
							{ selectedLesson.steps.map( ( step ) => (
								<li key={ step }>{ step }</li>
							) ) }
						</ol>
						<Button
							variant="primary"
							onClick={ () =>
								onNavigate( selectedLesson.destination )
							}
						>
							{ selectedLesson.action }
						</Button>
					</article>
				</div>
			</section>

			<section
				className="itsdz-guide-faq"
				aria-labelledby="itsdz-faq-title"
			>
				<div className="itsdz-guide-section-heading">
					<span>{ __( 'Quick answers', 'doczur' ) }</span>
					<h2 id="itsdz-faq-title">
						{ __( 'Questions new users often ask', 'doczur' ) }
					</h2>
				</div>
				<div>
					<details>
						<summary>
							{ __( 'Do I need to know code?', 'doczur' ) }
						</summary>
						<p>
							{ __(
								'No. Creating and organizing documentation happens inside the Doczur screens in WordPress.',
								'doczur'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'Can visitors search my articles?',
								'doczur'
							) }
						</summary>
						<p>
							{ __(
								'Yes. Published articles appear in the fast search on your visitor-facing documentation page.',
								'doczur'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'What is the difference between Draft and Published?',
								'doczur'
							) }
						</summary>
						<p>
							{ __(
								'A draft is private while you work on it. A published article can be read and searched by visitors.',
								'doczur'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'How do I back up my documentation?',
								'doczur'
							) }
						</summary>
						<p>
							{ __(
								'Open Import / Export and download a JSON backup. The file includes articles, tags, and the full section hierarchy.',
								'doczur'
							) }
						</p>
					</details>
				</div>
			</section>
		</div>
	);
}
