import {
	register,
	createReduxStore,
	select,
	dispatch,
	useSelect,
} from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { Post, Context, store as coreStore } from '@wordpress/core-data';
import { uniq, cloneDeep, isNumber, map, isEmpty, find, filter } from 'lodash';
import { apiFetch, controls as wpControls } from '@wordpress/data-controls';
import { APIFetchOptions } from '@wordpress/api-fetch';

//@ts-ignore.
const localizedData = window.mshmnStore || {};
const { currentUserId } = localizedData;

interface Role {
	id: number;
	name: string;
	prefix: string;
	conjunction: string;
	avatar_visibility: boolean;
	icon: number;
}
interface RoleAssignmentNumerical {
	role: number;
	contributors: number[]; //string of numbers comma seperated
}
interface PostMeta {
	mshmn_role_assignments: RoleAssignmentNumerical[];
	[key: string]: any; // Ensure compatibility with index signature
}
type RoleAssignement = {
	role: Role | null;
	contributors: Contributor[] | [];
};

interface State {
	roleAssignments: RoleAssignement[];
	isLoading: boolean;
	roles: Role[] | [];
	author?: Contributor;
}

interface Contributor {
	id: number;
	name: string;
	nicename: string;
	description: string;
	avatar?: number;
	type: 'user' | 'guest';
}

// type Action = RoleAssingmentsAction & ContributerAction;

type PostRecord<T extends Context> = Post<T> & {
	meta: PostMeta;
};
const DEFAULT_STATE: State = {
	roleAssignments: [{ role: null, contributors: [] }],
	isLoading: false,
	roles: [],
};
interface SetRoles {
	type: 'SET_ROLES';
	roles: Role[];
}
interface SetRoleAssignmentsAction {
	type: 'SET_ROLE_ASSIGNMENTS';
	roleAssignments: RoleAssignement[];
}

interface AddRoleAssignmentAction {
	type: 'ADD_ROLE_ASSIGNMENT';
	role: Role;
	assignmentIndex: number;
}

interface AddEmptyRoleAssignement {
	type: 'ADD_EMPTY_ROLE_ASSIGNMENT';
}

interface RemoveRoleAssignmentAction {
	type: 'REMOVE_ROLE_ASSIGNEMENT';
	assignmentIndex: number;
}

interface AddContributorAction {
	type: 'ADD_CONTRIBUTOR';
	contributor: Contributor;
	roleAssignmentIndex: number;
}

interface RemoveContributorAction {
	type: 'REMOVE_CONTRIBUTOR';
	contributorId: number;
}

interface FetchRoleAssignmentsAction {
	type: 'FETCH_ROLE_ASSIGNEMENTS';
}

interface FetchRoleAssignmentsSuccessAction {
	type: 'FETCH_ROLE_ASSIGNEMENTS_SUCCESS';
}

interface FetchRoleAssignmentsFailureAction {
	type: 'FETCH_ROLE_ASSIGNEMENTS_FAILURE';
	error: any;
}

interface SetCurrentPostAuthor {
	type: 'SET_CURRENT_POST_AUTHOR';
	author: Contributor;
}
type Action =
	| SetRoleAssignmentsAction
	| SetRoles
	| AddRoleAssignmentAction
	| AddEmptyRoleAssignement
	| RemoveRoleAssignmentAction
	| AddContributorAction
	| RemoveContributorAction
	| FetchRoleAssignmentsAction
	| FetchRoleAssignmentsSuccessAction
	| FetchRoleAssignmentsFailureAction
	| SetCurrentPostAuthor;

const ROLE_PATH = '/mshmn/v1/roles';
const CONTRIBUTOR_PATH = '/mshmn/v1/contributors';
const initialState = DEFAULT_STATE;

export const updatePostMeta = (
	roleAssignment: RoleAssignement[],
	allPostContributorIds: []
): void => {
	const { getCurrentPost } = select(editorStore) as {
		getCurrentPost: () => PostRecord<'edit'> | undefined | null;
	};

	const record = getCurrentPost();
	if (!record || !record.id || !record.type) {
		console.warn('Post record not ready. Skipping meta update.');
		return;
	}

	const postId = record?.id ?? '';
	const postType = record?.type ?? '';
	const { editEntityRecord } = dispatch(coreStore);
	const contributorsByRoles = roleAssignment?.map((roleAssignment) => {
		return {
			role: roleAssignment?.role?.id ?? null,
			contributors: map(roleAssignment?.contributors, 'id') ?? [],
		};
	});

	if (!isEmpty(contributorsByRoles)) {
		editEntityRecord?.('postType', postType, postId, {
			meta: {
				...(cloneDeep(record?.meta) ?? {}),
				mshmn_role_assignments: contributorsByRoles,
				mshmn_all_post_contributor_ids:
					allPostContributorIds?.join(','),
			},
		});
	}
};
// Actions
const actions = {
	setRoleAssignments(
		roleAssignments: RoleAssignement[]
	): SetRoleAssignmentsAction {
		return {
			type: 'SET_ROLE_ASSIGNMENTS',
			roleAssignments,
		};
	},
	setRoles(roles: Role[]): SetRoles {
		return {
			type: 'SET_ROLES',
			roles,
		};
	},
	addEmptyRoleAssignment(): AddEmptyRoleAssignement {
		return {
			type: 'ADD_EMPTY_ROLE_ASSIGNMENT',
		};
	},
	addRoleAssignment(
		role: Role,
		assignmentIndex: number
	): AddRoleAssignmentAction {
		return {
			type: 'ADD_ROLE_ASSIGNMENT',
			role,
			assignmentIndex,
		};
	},
	removeRoleAssignement(assignmentIndex: number): RemoveRoleAssignmentAction {
		return {
			type: 'REMOVE_ROLE_ASSIGNEMENT',
			assignmentIndex,
		};
	},
	addContributor(
		contributor: Contributor,
		roleAssignmentIndex: number
	): AddContributorAction {
		return {
			type: 'ADD_CONTRIBUTOR',
			contributor,
			roleAssignmentIndex,
		};
	},
	removeContributor(
		contributorId: Contributor['id']
	): RemoveContributorAction {
		return {
			type: 'REMOVE_CONTRIBUTOR',
			contributorId,
		};
	},
	setCurrentPostAuthor(contributor: Contributor): SetCurrentPostAuthor {
		return {
			type: 'SET_CURRENT_POST_AUTHOR',
			author: contributor,
		};
	},
};

const controls = {
	...wpControls,
};

// Reducer
const reducer = (state: State = DEFAULT_STATE, action: Action): State => {
	switch (action.type) {
		case 'SET_ROLE_ASSIGNMENTS':
			return {
				...cloneDeep(state),
				roleAssignments: !isEmpty(action.roleAssignments)
					? action.roleAssignments
					: state.roleAssignments,
			};
		case 'SET_ROLES':
			return {
				...cloneDeep(state),
				roles: action.roles ?? state.roles,
			};
		case 'ADD_EMPTY_ROLE_ASSIGNMENT':
			const currentState = cloneDeep(state);
			return {
				...currentState,
				roleAssignments: [
					...currentState.roleAssignments,
					...DEFAULT_STATE['roleAssignments'],
				],
			};
		case 'ADD_ROLE_ASSIGNMENT':
			const assignementIndex = action.assignmentIndex;
			const updatedAssignments = cloneDeep(state.roleAssignments);
			const preRoleIds = map(updatedAssignments, 'role.id') as
				| number[]
				| undefined
				| null;
			const currentRoleId = action.role?.id;
			const isSameId = preRoleIds?.includes(currentRoleId);
			updatedAssignments[assignementIndex].role = !isSameId
				? action.role
				: null;

			return {
				...cloneDeep(state),
				roleAssignments: updatedAssignments,
			};
		case 'REMOVE_ROLE_ASSIGNEMENT':
			if (0 === action.assignmentIndex) {
				return state;
			}
			return {
				...cloneDeep(state),
				roleAssignments: cloneDeep(state).roleAssignments.filter(
					(_roleAssignement: RoleAssignement, index: number) =>
						index !== action.assignmentIndex
				),
			};
		case 'ADD_CONTRIBUTOR':
			const contributor = action.contributor;
			const index = action.roleAssignmentIndex;
			const updatedRoleAssignments = cloneDeep(state.roleAssignments);
			const prevContributors = [
				...updatedRoleAssignments[index].contributors,
			];
			updatedRoleAssignments[index].contributors = uniq([
				...prevContributors,
				contributor,
			]);
			return {
				...cloneDeep(state),
				roleAssignments: updatedRoleAssignments,
			};

		case 'REMOVE_CONTRIBUTOR':
			const roleAssignments = state.roleAssignments;
			if (!isNumber(action.contributorId)) return state;
			const ReupdatedRoleAssignments = roleAssignments.map(
				(assignement) => {
					const filteredContributors =
						assignement?.contributors.filter(
							(contributor) =>
								contributor?.id !== action.contributorId
						);
					return {
						...assignement,
						contributors: filteredContributors,
					};
				}
			);
			return {
				...cloneDeep(state),
				roleAssignments: ReupdatedRoleAssignments,
			};
		case 'SET_CURRENT_POST_AUTHOR':
			const author = action.author;
			return {
				...cloneDeep(state),
				author: author,
			};
		default:
			return state;
	}
};

// Selectors
const selectors = {
	getRoleAssignments(state: State): RoleAssignement[] | undefined {
		return state.roleAssignments;
	},
	getRoles(state: State): Role[] {
		return state.roles;
	},
	getCurrentPostAuthor(state: State): Contributor | undefined {
		return state.author;
	},
};

const reconstructRoleAssignmentsFromIds = (
	roleEntities: Role[],
	contributorEntities: Contributor[],
	roleAssignmentNumerical: RoleAssignmentNumerical[]
) => {
	//ids to entities.
	return roleAssignmentNumerical?.map((assignment) => {
		const role = find(roleEntities, { id: assignment.role }) ?? null;
		const contributorsByrole = filter(
			assignment?.contributors?.map((contributorId) => {
				return find(contributorEntities, { id: contributorId });
			}),
			(contributor: Contributor) => contributor !== undefined
		);
		return { role: role, contributors: contributorsByrole };
	});
};

const extractContributorIdsString = (
	contributorsByRoles: RoleAssignmentNumerical[]
) => {
	if (isEmpty(contributorsByRoles)) {
		return '';
	}
	const roleAssignmentNumerical = contributorsByRoles;
	const combinedString = map(roleAssignmentNumerical, 'contributors').join(
		','
	);
	return combinedString;
};

// Resolvers
// TODO
const resolvers = {
	*getRoleAssignments(): Generator<
		| Action
		| {
				type: string;
				request: APIFetchOptions;
		  },
		Action | void,
		PostRecord<'edit'> | Role[] | Contributor[]
	> {
		const params = new URLSearchParams(window.location.search);
		const postId = params.get('post');

		try {
			if (!postId) {
				yield actions.setRoleAssignments([
					{ role: null, contributors: [] },
				]);
				return;
			}
			const record = yield apiFetch({ path: `wp/v2/posts/${postId}` });
			const contributorsByRoles = record?.meta
				.mshmn_role_assignments as RoleAssignmentNumerical[];
			const contributorsIdsString = extractContributorIdsString(
				contributorsByRoles ?? []
			);
			const rolePath = ROLE_PATH; // roles are not a lot, and we'll also neeed them all for role selection.
			const contributorsPath = `${CONTRIBUTOR_PATH}?ids=${contributorsIdsString}`;
			const roles = yield apiFetch({ path: rolePath });
			const contributors = yield apiFetch({ path: contributorsPath });

			const reconstructedRoleAssignments =
				reconstructRoleAssignmentsFromIds(
					roles as Role[],
					contributors as Contributor[],
					contributorsByRoles ?? []
				);
			yield actions.setRoleAssignments(reconstructedRoleAssignments);
		} catch (error) {
			return console.error(error);
		}
	},
	*getCurrentPostAuthor(): Generator<
		Action | { type: string; request: APIFetchOptions },
		Action | void,
		PostRecord<'edit'> | Contributor[]
	> {
		const params = new URLSearchParams(window.location.search);
		const postId = params.get('post');
		try {
			if (!postId) {
				const contributors = yield apiFetch({
					path: `${CONTRIBUTOR_PATH}?ids=${currentUserId}`,
				});
				yield actions.setCurrentPostAuthor(contributors[0] ?? null);
				return;
			}
			const record = yield apiFetch({ path: `wp/v2/posts/${postId}` });
			const authorId = record?.author ?? currentUserId ?? '';

			const contributors = yield apiFetch({
				path: `${CONTRIBUTOR_PATH}?ids=${authorId}`,
			});
			yield actions.setCurrentPostAuthor(contributors[0] ?? null);
		} catch (error) {
			return console.error(error);
		}
	},

	*getRoles(): Generator<
		Action | { type: string; request: APIFetchOptions },
		Action | void,
		Role[]
	> {
		try {
			const rolePath = ROLE_PATH; // roles are not a lot, and we'll also neeed them all for role selection.
			const roles = yield apiFetch({ path: rolePath });
			yield actions.setRoles(roles);
		} catch (error) {
			return console.error(error);
		}
	},
};

export const store = createReduxStore('bibio/store', {
	reducer,
	actions,
	selectors,
	controls,
	resolvers,
	initialState,
});

export const registerStore = () => register(store);
