import { registerStore, store, updatePostMeta } from './store/index';
import domReady from '@wordpress/dom-ready';
import { subscribe, select } from '@wordpress/data';
import { isEmpty, map, flatten } from 'lodash';

function getAllContributorIds(roleAssignments) {
	const contributors = map(roleAssignments, 'contributors');
	return map(flatten(contributors), 'id');
}
// Later, if necessary...
domReady(() => {
	registerStore();

	subscribe(() => {
		const updateRoleAssignments = select(store)?.getRoleAssignments();
		const allPostContributorIds = getAllContributorIds(
			updateRoleAssignments
		);
		if (!isEmpty(updateRoleAssignments)) {
			updatePostMeta(updateRoleAssignments, allPostContributorIds);
		}
	}, store);
});
