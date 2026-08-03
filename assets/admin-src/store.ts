import { createReduxStore, register } from '@wordpress/data';
import type { Article, Project, Section } from './types';

export const STORE_NAME = 'itsdz/store';

interface State {
	projects: Project[];
	articles: Article[];
	sections: Section[];
	selectedProjectId: number | null;
	loading: boolean;
	notice: { status: 'success' | 'error'; message: string } | null;
}

const DEFAULT_STATE: State = {
	projects: [],
	articles: [],
	sections: [],
	selectedProjectId: null,
	loading: true,
	notice: null,
};

export const store = createReduxStore( STORE_NAME, {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_PROJECTS':
				return { ...state, projects: action.projects };
			case 'SET_ARTICLES':
				return { ...state, articles: action.articles };
			case 'SET_SECTIONS':
				return { ...state, sections: action.sections };
			case 'SELECT_PROJECT':
				return { ...state, selectedProjectId: action.projectId };
			case 'SET_LOADING':
				return { ...state, loading: action.loading };
			case 'SET_NOTICE':
				return { ...state, notice: action.notice };
			default:
				return state;
		}
	},
	actions: {
		setProjects: ( projects: Project[] ) => ( {
			type: 'SET_PROJECTS',
			projects,
		} ),
		setArticles: ( articles: Article[] ) => ( {
			type: 'SET_ARTICLES',
			articles,
		} ),
		setSections: ( sections: Section[] ) => ( {
			type: 'SET_SECTIONS',
			sections,
		} ),
		selectProject: ( projectId: number | null ) => ( {
			type: 'SELECT_PROJECT',
			projectId,
		} ),
		setLoading: ( loading: boolean ) => ( {
			type: 'SET_LOADING',
			loading,
		} ),
		setNotice: ( notice: State[ 'notice' ] ) => ( {
			type: 'SET_NOTICE',
			notice,
		} ),
	},
	selectors: {
		getProjects: ( state: State ) => state.projects,
		getArticles: ( state: State ) => state.articles,
		getSections: ( state: State ) => state.sections,
		getSelectedProjectId: ( state: State ) => state.selectedProjectId,
		isLoading: ( state: State ) => state.loading,
		getNotice: ( state: State ) => state.notice,
	},
} );

register( store );
