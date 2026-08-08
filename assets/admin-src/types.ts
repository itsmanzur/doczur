export interface ProjectMeta {
	_itsdz_kb_logo: number;
	_itsdz_kb_brand_color: string;
	_itsdz_kb_theme_mode: 'light' | 'dark' | 'system';
	_itsdz_kb_template: 'clean' | 'modern' | 'compact';
	_itsdz_kb_doc_type: string;
	_itsdz_kb_slug_base: string;
	_itsdz_kb_layout_mode: 'canvas' | 'theme';
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

declare global {
	interface Window {
		itsdzAdmin: AdminConfiguration;
	}
}
