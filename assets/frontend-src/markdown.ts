/**
 * Minimal HTML to Markdown conversion for article content.
 *
 * A general-purpose converter would be far larger than the whole frontend
 * budget allows. This one only has to handle the elements the Nirdeshio editor
 * can actually produce, so it walks the rendered DOM directly instead.
 */

const SKIPPED_TAGS = new Set( [ 'SCRIPT', 'STYLE', 'NOSCRIPT', 'BUTTON' ] );

const CALLOUT_LABELS: Record< string, string > = {
	'itsdz-callout-info': 'Note',
	'itsdz-callout-tip': 'Tip',
	'itsdz-callout-warning': 'Warning',
	'itsdz-callout-danger': 'Caution',
};

/**
 * Collapse the whitespace inside a run of inline text.
 */
function inlineText( node: Node ): string {
	return ( node.textContent ?? '' ).replace( /\s+/g, ' ' );
}

/**
 * Render the children of an element as inline Markdown.
 */
function inlineChildren( element: Element ): string {
	let out = '';

	element.childNodes.forEach( ( child ) => {
		out += inline( child );
	} );

	return out;
}

/**
 * Convert an inline node (text, emphasis, link, code…) to Markdown.
 */
function inline( node: Node ): string {
	if ( node.nodeType === Node.TEXT_NODE ) {
		return inlineText( node );
	}

	if ( node.nodeType !== Node.ELEMENT_NODE ) {
		return '';
	}

	const element = node as Element;

	if ( SKIPPED_TAGS.has( element.tagName ) ) {
		return '';
	}

	switch ( element.tagName ) {
		case 'STRONG':
		case 'B':
			return `**${ inlineChildren( element ) }**`;
		case 'EM':
		case 'I':
			return `*${ inlineChildren( element ) }*`;
		case 'CODE':
			return `\`${ inlineText( element ) }\``;
		case 'BR':
			return '\n';
		case 'A': {
			const href = element.getAttribute( 'href' ) ?? '';
			const label = inlineChildren( element ).trim();
			return href ? `[${ label }](${ href })` : label;
		}
		case 'IMG': {
			const src = element.getAttribute( 'src' ) ?? '';
			const alt = element.getAttribute( 'alt' ) ?? '';
			return src ? `![${ alt }](${ src })` : '';
		}
		default:
			return inlineChildren( element );
	}
}

/**
 * Render a table as a GitHub-flavoured Markdown table.
 */
function convertTable( table: Element ): string {
	const rows = Array.from( table.querySelectorAll( 'tr' ) );

	if ( rows.length === 0 ) {
		return '';
	}

	const toCells = ( row: Element ) =>
		Array.from( row.querySelectorAll( 'th, td' ) ).map( ( cell ) =>
			inlineChildren( cell ).trim().replace( /\|/g, '\\|' )
		);

	const header = toCells( rows[ 0 ] );
	const lines = [ `| ${ header.join( ' | ' ) } |`, `| ${ header.map( () => '---' ).join( ' | ' ) } |` ];

	rows.slice( 1 ).forEach( ( row ) => {
		lines.push( `| ${ toCells( row ).join( ' | ' ) } |` );
	} );

	return lines.join( '\n' );
}

/**
 * Render a list, indenting any nested lists.
 */
function convertList( list: Element, ordered: boolean, depth: number ): string {
	const indent = '  '.repeat( depth );
	const items: string[] = [];
	let index = 1;

	Array.from( list.children ).forEach( ( item ) => {
		if ( item.tagName !== 'LI' ) {
			return;
		}

		const marker = ordered ? `${ index++ }.` : '-';
		const nested: string[] = [];
		let text = '';

		item.childNodes.forEach( ( child ) => {
			if ( child.nodeType === Node.ELEMENT_NODE ) {
				const el = child as Element;

				if ( el.tagName === 'UL' || el.tagName === 'OL' ) {
					nested.push( convertList( el, el.tagName === 'OL', depth + 1 ) );
					return;
				}
			}

			text += inline( child );
		} );

		items.push( `${ indent }${ marker } ${ text.trim() }` );

		nested.forEach( ( block ) => items.push( block ) );
	} );

	return items.join( '\n' );
}

/**
 * Convert one block-level element to Markdown.
 */
function convertBlock( element: Element ): string {
	if ( SKIPPED_TAGS.has( element.tagName ) ) {
		return '';
	}

	switch ( element.tagName ) {
		case 'H1':
			return `# ${ inlineChildren( element ).trim() }`;
		case 'H2':
			return `## ${ inlineChildren( element ).trim() }`;
		case 'H3':
			return `### ${ inlineChildren( element ).trim() }`;
		case 'H4':
			return `#### ${ inlineChildren( element ).trim() }`;
		case 'P':
			return inlineChildren( element ).trim();
		case 'UL':
			return convertList( element, false, 0 );
		case 'OL':
			return convertList( element, true, 0 );
		case 'PRE': {
			const code = element.querySelector( 'code' );
			const body = ( code ? code.textContent : element.textContent ) ?? '';
			return `\`\`\`\n${ body.replace( /\n+$/, '' ) }\n\`\`\``;
		}
		case 'BLOCKQUOTE':
			return inlineChildren( element )
				.trim()
				.split( '\n' )
				.map( ( line ) => `> ${ line }` )
				.join( '\n' );
		case 'TABLE':
			return convertTable( element );
		case 'HR':
			return '---';
		case 'FIGURE': {
			const image = element.querySelector( 'img' );
			const caption = element.querySelector( 'figcaption' );
			const parts: string[] = [];

			if ( image ) {
				parts.push( inline( image ) );
			}
			if ( caption ) {
				parts.push( `*${ inlineChildren( caption ).trim() }*` );
			}

			return parts.join( '\n\n' );
		}
		case 'DETAILS': {
			const summary = element.querySelector( 'summary' );
			const heading = summary ? inlineChildren( summary ).trim() : '';
			const body = Array.from( element.children )
				.filter( ( child ) => child.tagName !== 'SUMMARY' )
				.map( ( child ) => convertBlock( child ) )
				.filter( Boolean )
				.join( '\n\n' );

			return heading ? `**${ heading }**\n\n${ body }`.trim() : body;
		}
		case 'DIV': {
			// Callouts carry their severity in a class; keep that meaning.
			const variant = Array.from( element.classList ).find( ( cls ) => cls in CALLOUT_LABELS );
			const body = convertChildren( element );

			if ( ! variant ) {
				return body;
			}

			return body
				.split( '\n' )
				.map( ( line ) => `> ${ line }`.trimEnd() )
				.join( '\n' )
				.replace( /^> /, `> **${ CALLOUT_LABELS[ variant ] }:** ` );
		}
		default:
			return convertChildren( element );
	}
}

/**
 * Convert every child of a container, separating blocks with blank lines.
 */
function convertChildren( container: Element ): string {
	const blocks: string[] = [];
	let pendingInline = '';

	const flushInline = () => {
		const text = pendingInline.trim();
		if ( text ) {
			blocks.push( text );
		}
		pendingInline = '';
	};

	container.childNodes.forEach( ( node ) => {
		if ( node.nodeType === Node.TEXT_NODE ) {
			pendingInline += inlineText( node );
			return;
		}

		if ( node.nodeType !== Node.ELEMENT_NODE ) {
			return;
		}

		const element = node as Element;
		const isBlock = /^(H[1-6]|P|UL|OL|PRE|BLOCKQUOTE|TABLE|HR|FIGURE|DETAILS|DIV|SECTION)$/.test(
			element.tagName
		);

		if ( ! isBlock ) {
			pendingInline += inline( element );
			return;
		}

		flushInline();

		const converted = convertBlock( element ).trim();

		if ( converted ) {
			blocks.push( converted );
		}
	} );

	flushInline();

	return blocks.join( '\n\n' );
}

/**
 * Convert a rendered article into a Markdown document.
 *
 * @param content Element wrapping the article body.
 * @param title   Article title, used as the top-level heading.
 * @param url     Canonical URL, recorded as a source line.
 */
export function articleToMarkdown( content: Element, title: string, url: string ): string {
	const parts = [ `# ${ title.trim() }` ];

	if ( url ) {
		parts.push( `_Source: ${ url }_` );
	}

	const body = convertChildren( content );

	if ( body ) {
		parts.push( body );
	}

	return `${ parts.join( '\n\n' ) }\n`;
}
