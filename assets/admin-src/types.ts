export interface ProjectMeta {
	_itsdz_kb_logo: number;
	_itsdz_kb_brand_color: string;
	_itsdz_kb_theme_mode: 'light' | 'dark' | 'system';
	_itsdz_kb_template: 'clean' | 'modern' | 'compact';
	_itsdz_kb_doc_type: string;
	_itsdz_kb_slug_base: string;
	_itsdz_kb_layout_mode: 'canvas' | 'theme';
	_itsdz_kb_nav_style: 'accordion' | 'rail' | 'line' | 'tree';
	_itsdz_kb_show_toc: '0' | '1';
	_itsdz_kb_show_feedback: '0' | '1';
	_itsdz_kb_show_related: '0' | '1';
	_itsdz_kb_show_print: '0' | '1';
	_itsdz_kb_custom_css: string;
	_itsdz_kb_header_links: string;
	_itsdz_kb_active_version: number;
}

export interface Project {
	id: number;
	title: string;
	content: string;
	slug: string;
	status: 'draft' | 'publish';
	url: string;
	meta: ProjectMeta;
}

export interface Article {
	id: number;
	kb_id: number;
	title: string;
	content: string;
	slug: string;
	status: 'draft' | 'publish';
	menu_order: number;
	reading_time: number;
	/** ISO date (YYYY-MM-DD) the article was last reviewed, or '' when never. */
	last_reviewed: string;
	/** RFC3339 timestamp of the last content edit. */
	modified: string;
	section_ids: number[];
	tag_ids: number[];
	version_id: number;
	url: string;
}

export interface Section {
	id: number;
	name: string;
	parent: number;
	count: number;
	description?: string;
	meta?: {
		_itsdz_section_icon?: string;
	};
}

export interface HeaderLink {
	label: string;
	url: string;
}

export interface AdminConfiguration {
	pluginVersion: string;
	restNonce: string;
	restRoot: string;
	siteName: string;
	user: {
		displayName: string;
		id: number;
	};
}

interface WpMediaAttachment {
	id: number;
	url: string;
	sizes?: {
		thumbnail?: { url: string };
	};
}

interface WpMediaFrame {
	on( event: 'select', callback: () => void ): void;
	open(): void;
	state(): {
		get: ( key: 'selection' ) => {
			first: () => { toJSON: () => WpMediaAttachment };
		};
	};
}

declare global {
	interface Window {
		itsdzAdmin: AdminConfiguration;
		wp?: {
			media: ( args: Record< string, unknown > ) => WpMediaFrame;
		};
	}
}
