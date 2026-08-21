import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { Article, Project } from '../types';

type GuideDestination = 'docs' | 'glossary' | 'settings' | 'transfer';

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
			label: __( 'Documentation portal created', 'itsmanzur-docs' ),
			done: true,
		},
		{
			label: __( 'First article added', 'itsmanzur-docs' ),
			done: articles.length > 0,
		},
		{
			label: __( 'At least one article published', 'itsmanzur-docs' ),
			done: publishedArticles > 0,
		},
		{
			label: __( 'Help center ready to share', 'itsmanzur-docs' ),
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
			title: __( 'Understand the basics', 'itsmanzur-docs' ),
			summary: __( 'Learn the three simple building blocks.', 'itsmanzur-docs' ),
			heading: __(
				'Think of Nirdeshio as an organized help center',
				'itsmanzur-docs'
			),
			description: __(
				'Your documentation project is the whole help center. Sections work like folders, and articles are the helpful answers inside those folders.',
				'itsmanzur-docs'
			),
			steps: [
				__( 'Project: the complete documentation website.', 'itsmanzur-docs' ),
				__( 'Section: a group such as Installation or FAQ.', 'itsmanzur-docs' ),
				__(
					'Article: one clear answer to one customer question.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Open documentation builder', 'itsmanzur-docs' ),
			destination: 'docs',
		},
		{
			id: 'write',
			number: '02',
			title: __( 'Write and publish', 'itsmanzur-docs' ),
			summary: __(
				'Create a useful article without technical work.',
				'itsmanzur-docs'
			),
			heading: __(
				'Start with the question customers ask most',
				'itsmanzur-docs'
			),
			description: __(
				'Clicking "+ New" opens the article straight in the WordPress block editor. Write your steps there, then set the section, tags and version in the "Nirdeshio" panel on the right before publishing.',
				'itsmanzur-docs'
			),
			steps: [
				__(
					'Click "+ New" in the Documentation screen — it opens the editor immediately.',
					'itsmanzur-docs'
				),
				__(
					'Use a title that matches the customer question.',
					'itsmanzur-docs'
				),
				__(
					'Open the "Nirdeshio" panel in the sidebar to set the section, tags and version.',
					'itsmanzur-docs'
				),
				__(
					'Save as Draft while writing, then choose Publish when it is ready.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Write an article', 'itsmanzur-docs' ),
			destination: 'docs',
		},
		{
			id: 'format',
			number: '03',
			title: __( 'Format with confidence', 'itsmanzur-docs' ),
			summary: __( 'Write visually — you never see a tag.', 'itsmanzur-docs' ),
			heading: __( 'Article content is written in the WordPress block editor', 'itsmanzur-docs' ),
			description: __(
				'Click "Edit content in Gutenberg" on any article to open the same visual, block-based editor you already use for posts and pages. Every block you add — paragraphs, images, tables, lists — is fully WYSIWYG.',
				'itsmanzur-docs'
			),
			steps: [
				__(
					'On the Documentation screen, click "Edit content in Gutenberg" for any article.',
					'itsmanzur-docs'
				),
				__(
					'Add the "Nirdeshio Callout" block for coloured notes, tips, warnings and cautions.',
					'itsmanzur-docs'
				),
				__(
					'Use core WordPress blocks for images, tables and collapsible lists.',
					'itsmanzur-docs'
				),
				__(
					'Save or publish, then return to Nirdeshio — the preview updates automatically.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Open the documentation tree', 'itsmanzur-docs' ),
			destination: 'docs',
		},
		{
			id: 'glossary',
			number: '04',
			title: __( 'Explain your jargon', 'itsmanzur-docs' ),
			summary: __( 'Define a term once, everywhere.', 'itsmanzur-docs' ),
			heading: __( 'Readers hover, and the definition appears', 'itsmanzur-docs' ),
			description: __(
				'Add the words your product uses to the glossary. The first time each term appears in an article it gets a dotted underline, and the definition shows on hover or keyboard focus. Links, headings and code samples are never touched.',
				'itsmanzur-docs'
			),
			steps: [
				__( 'Open Glossary and add a term with its definition.', 'itsmanzur-docs' ),
				__(
					'Add alternative spellings so plurals and abbreviations match too.',
					'itsmanzur-docs'
				),
				__(
					'Use the Nirdeshio Glossary block to publish the full list on its own page.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Manage the glossary', 'itsmanzur-docs' ),
			destination: 'glossary',
		},
		{
			id: 'design',
			number: '05',
			title: __( 'Make it yours', 'itsmanzur-docs' ),
			summary: __( 'Match the help center to your brand.', 'itsmanzur-docs' ),
			heading: __(
				'Choose the look—Nirdeshio handles the layout',
				'itsmanzur-docs'
			),
			description: __(
				'Add your project name, URL, brand color, color mode, and template. You can use the clean standalone canvas or keep your WordPress theme around the content.',
				'itsmanzur-docs'
			),
			steps: [
				__(
					'Open Settings and confirm the project name and URL.',
					'itsmanzur-docs'
				),
				__( 'Choose a brand color with good text contrast.', 'itsmanzur-docs' ),
				__( 'Save, then preview the visitor-facing page.', 'itsmanzur-docs' ),
			],
			action: __( 'Customize the design', 'itsmanzur-docs' ),
			destination: 'settings',
		},
		{
			id: 'maintain',
			number: '06',
			title: __( 'Share and maintain', 'itsmanzur-docs' ),
			summary: __( 'Keep documentation useful over time.', 'itsmanzur-docs' ),
			heading: __(
				'Share one link and improve answers as you learn',
				'itsmanzur-docs'
			),
			description: __(
				'Send customers to your documentation home page. Mark articles as reviewed when you check them, so anything drifting out of date is easy to spot, and download a JSON backup before large edits.',
				'itsmanzur-docs'
			),
			steps: [
				__(
					'Copy the live documentation URL and add it to your menu.',
					'itsmanzur-docs'
				),
				__(
					'Use "Mark reviewed today" after checking an article is still accurate.',
					'itsmanzur-docs'
				),
				__(
					'Articles left unreviewed for 90 days show a "Needs review" badge.',
					'itsmanzur-docs'
				),
				__(
					'Download an export before major content changes.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Open Import / Export', 'itsmanzur-docs' ),
			destination: 'transfer',
		},
		{
			id: 'ai',
			number: '07',
			title: __( 'Be readable by AI', 'itsmanzur-docs' ),
			summary: __( 'Help assistants answer from your docs.', 'itsmanzur-docs' ),
			heading: __(
				'Your documentation already speaks to AI assistants',
				'itsmanzur-docs'
			),
			description: __(
				'Customers increasingly ask ChatGPT, Claude or Perplexity instead of searching your site. Nirdeshio publishes a machine-readable map of your documentation automatically, so those answers come from your actual content rather than guesswork.',
				'itsmanzur-docs'
			),
			steps: [
				__(
					'Visit /llms.txt on your site to see the generated index.',
					'itsmanzur-docs'
				),
				__(
					'/llms-full.txt adds the complete text of every published article.',
					'itsmanzur-docs'
				),
				__(
					'Readers can use "Copy as Markdown" on any article to paste it into a chat.',
					'itsmanzur-docs'
				),
				__(
					'Only published articles in published projects are ever included.',
					'itsmanzur-docs'
				),
			],
			action: __( 'Review your articles', 'itsmanzur-docs' ),
			destination: 'docs',
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
						{ __( 'Welcome to Nirdeshio', 'itsmanzur-docs' ) }
					</span>
					<h1 id="itsdz-guide-title">
						{ __(
							'Documentation made simple—for you and your customers.',
							'itsmanzur-docs'
						) }
					</h1>
					<p>
						{ __(
							'Nirdeshio turns your WordPress site into a clean, searchable help center. You organize answers, publish them, and share one easy link. No coding is required.',
							'itsmanzur-docs'
						) }
					</p>
					<div className="itsdz-guide-actions">
						<Button
							variant="primary"
							onClick={ () => onNavigate( 'docs' ) }
						>
							{ isReady
								? __( 'Manage documentation', 'itsmanzur-docs' )
								: __( 'Continue building', 'itsmanzur-docs' ) }
						</Button>
						{ project.url && (
							<Button
								variant="secondary"
								href={ project.url }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'View live documentation', 'itsmanzur-docs' ) }
							</Button>
						) }
					</div>
					<ul
						className="itsdz-guide-benefits"
						aria-label={ __( 'Nirdeshio benefits', 'itsmanzur-docs' ) }
					>
						<li>{ __( 'No coding required', 'itsmanzur-docs' ) }</li>
						<li>{ __( 'Fast visitor search', 'itsmanzur-docs' ) }</li>
						<li>{ __( 'Mobile friendly', 'itsmanzur-docs' ) }</li>
						<li>{ __( 'Article tags & versions', 'itsmanzur-docs' ) }</li>
						<li>{ __( 'View analytics built-in', 'itsmanzur-docs' ) }</li>
					</ul>
				</div>

				<aside
					className="itsdz-guide-progress"
					aria-label={ __( 'Launch progress', 'itsmanzur-docs' ) }
				>
					<div className="itsdz-progress-heading">
						<div>
							<span>
								{ __( 'Your launch progress', 'itsmanzur-docs' ) }
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
								'itsmanzur-docs'
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
					<span>{ __( 'The simple idea', 'itsmanzur-docs' ) }</span>
					<h2 id="itsdz-model-title">
						{ __( 'How Nirdeshio is organized', 'itsmanzur-docs' ) }
					</h2>
					<p>
						{ __(
							'Three building blocks are all you need to remember.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
				<div className="itsdz-model-grid">
					{ [
						[
							'1',
							__( 'Project', 'itsmanzur-docs' ),
							__(
								'Your complete help center—for example, “Acme App Help”.',
								'itsmanzur-docs'
							),
						],
						[
							'2',
							__( 'Section', 'itsmanzur-docs' ),
							__(
								'A folder that groups similar answers, such as Getting Started.',
								'itsmanzur-docs'
							),
						],
						[
							'3',
							__( 'Article', 'itsmanzur-docs' ),
							__(
								'One useful answer, such as “How to install the app”.',
								'itsmanzur-docs'
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
					<span>{ __( 'Guided walkthrough', 'itsmanzur-docs' ) }</span>
					<h2 id="itsdz-lessons-title">
						{ __( 'Choose what you want to do', 'itsmanzur-docs' ) }
					</h2>
					<p>
						{ __(
							'Select a step to see clear instructions and the right next action.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
				<div className="itsdz-lesson-layout">
					<div
						className="itsdz-lesson-tabs"
						role="tablist"
						aria-label={ __( 'Nirdeshio lessons', 'itsmanzur-docs' ) }
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

			{ /* ─── What's New ─── */ }
			<section
				className="itsdz-guide-features"
				aria-labelledby="itsdz-features-title"
			>
				<div className="itsdz-guide-section-heading">
					<span>{ __( "What's new", 'itsmanzur-docs' ) }</span>
					<h2 id="itsdz-features-title">
						{ __( 'Recently added features', 'itsmanzur-docs' ) }
					</h2>
					<p>
						{ __(
							'Everything below is available in the free version.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
				<div className="itsdz-features-grid">
					{ (
						[
							[
								'🏷️',
								__( 'Article Tags', 'itsmanzur-docs' ),
								__(
									'Assign tags to articles from the editor. Tags group related articles and display as clickable pills on the frontend.',
									'itsmanzur-docs'
								),
							],
							[
								'🔖',
								__( 'Version Selector', 'itsmanzur-docs' ),
								__(
									'Tag articles with a version label such as v1.0. Create new versions instantly using the + New version button in the editor.',
									'itsmanzur-docs'
								),
							],
							[
								'👁',
								__( 'View Analytics', 'itsmanzur-docs' ),
								__(
									'See how many times each article has been read. View counts appear next to articles in the tree panel and inside the editor toolbar.',
									'itsmanzur-docs'
								),
							],
							[
								'📋',
								__( 'Code Copy Button', 'itsmanzur-docs' ),
								__(
									'Every code block on the frontend shows a Copy button on hover. Visitors can copy code to the clipboard in one click.',
									'itsmanzur-docs'
								),
							],
							[
								'💬',
								__( 'Callout Variants', 'itsmanzur-docs' ),
								__(
									'Four callout styles—Info ℹ️, Warning ⚠️, Danger 🚫, and Tip 💡—available from the toolbar. Each has a distinct color and icon.',
									'itsmanzur-docs'
								),
							],
							[
								'📖',
								__( 'Reading Progress Bar', 'itsmanzur-docs' ),
								__(
									'A thin gradient bar at the top of the page shows visitors how far through an article they have scrolled.',
									'itsmanzur-docs'
								),
							],
							[
								'🔍',
								__( 'Search Highlight', 'itsmanzur-docs' ),
								__(
									'When a visitor arrives from the search modal, matching keywords are highlighted inside the article content automatically.',
									'itsmanzur-docs'
								),
							],
							[
								'🗂️',
								__( 'Rich Content Toolbar', 'itsmanzur-docs' ),
								__(
									'Insert images from the Media Library, embed YouTube or Vimeo videos, add tables with custom headers, and create animated accordion blocks—all from toolbar buttons.',
									'itsmanzur-docs'
								),
							],
						] as [ string, string, string ][]
					).map( ( [ icon, title, description ] ) => (
						<div className="itsdz-feature-card" key={ title }>
							<span className="itsdz-feature-icon" aria-hidden="true">
								{ icon }
							</span>
							<div>
								<strong>{ title }</strong>
								<p>{ description }</p>
							</div>
						</div>
					) ) }
				</div>
			</section>

			<section
				className="itsdz-guide-faq"
				aria-labelledby="itsdz-faq-title"
			>
				<div className="itsdz-guide-section-heading">
					<span>{ __( 'Quick answers', 'itsmanzur-docs' ) }</span>
					<h2 id="itsdz-faq-title">
						{ __( 'Questions new users often ask', 'itsmanzur-docs' ) }
					</h2>
				</div>
				<div>
					<details>
						<summary>
							{ __( 'Do I need to know code?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'No. Creating and organizing documentation happens inside the Nirdeshio screens in WordPress.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'Can visitors search my articles?',
								'itsmanzur-docs'
							) }
						</summary>
						<p>
							{ __(
								'Yes. Published articles appear in the fast search on your visitor-facing documentation page.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'What is the difference between Draft and Published?',
								'itsmanzur-docs'
							) }
						</summary>
						<p>
							{ __(
								'A draft is private while you work on it. A published article can be read and searched by visitors.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __(
								'How do I back up my documentation?',
								'itsmanzur-docs'
							) }
						</summary>
						<p>
							{ __(
								'Open Import / Export and download a JSON backup. The file includes articles, tags, and the full section hierarchy.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __( 'How do I add tags to an article?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'Open the article editor and scroll below the Status and Section fields. You will see a tag selector showing all available tags. Click any pill to toggle it. Tags are saved with the article automatically.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __( 'How do I create a version label?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'In the article editor, find the Version dropdown next to the section selector. Click + New version, type the label such as v2.0, and it will be created and selected instantly.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __( 'Where can I see article view counts?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'View counts are shown in two places: as a small badge next to the article title in the tree panel, and as an eye icon counter inside the editor toolbar when you open an article.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __( 'How do I insert a callout box?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'In the article content editor, use the four emoji buttons in the toolbar: ℹ️ for Info, ⚠️ for Warning, 🚫 for Danger, and 💡 for Tip. Each inserts a styled callout block with the correct color and icon.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
					<details>
						<summary>
							{ __( 'Does the frontend show a reading progress bar?', 'itsmanzur-docs' ) }
						</summary>
						<p>
							{ __(
								'Yes. When a visitor reads an article, a thin gradient bar at the very top of the browser window fills as they scroll through the content.',
								'itsmanzur-docs'
							) }
						</p>
					</details>
				</div>
			</section>
		</div>
	);
}
