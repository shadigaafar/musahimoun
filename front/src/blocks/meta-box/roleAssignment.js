import RoleSelector from './roleSelector';
import ContributorSearch from './contributorSearch';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { find, isEmpty, isInteger, map, uniqueId } from 'lodash';
import SelectedContributorsList from './selectedContributorsList';
import { store } from './../../store/index';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect, select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useMemo, useRef } from 'react';

function RoleAssignment() {
	const { record, authorId } = useSelect((select) => {
		return {
			record: select(editorStore).getCurrentPost() ?? {},
			authorId:
				select(editorStore).getEditedPostAttribute('author') ?? null,
		};
	}, []);

	const postId = record?.id ?? null;

	const isSetDefaultRoleAssignementRef = useRef(false);
	const {
		roles,
		roleAssignments,
		author,
		isResolvingAuthor,
		isResolving,
		isResolvingRoles,
	} = useSelect((select) => {
		const selectors = select(store);
		if (!selectors) {
			console.error('Mushaimoun: store problem, selectors is empty!');
		}
		return {
			author: selectors?.getCurrentPostAuthor(),
			roles: selectors?.getRoles(),
			roleAssignments: selectors?.getRoleAssignments(),
			isResolving: selectors?.isResolving('getRoleAssignments'),
			isResolvingAuthor: selectors?.isResolving('getCurrentPostAuthor'),
			isResolvingRoles: selectors?.isResolving('getRoles'),
		};
	}, []);

	const { mshmn_default_role } =
		useSelect(
			(select) => {
				return select(coreStore).getEntityRecord('root', 'site');
			},
			[postId]
		) || {};

	const {
		addRoleAssignment,
		removeRoleAssignement,
		addEmptyRoleAssignment,
		addContributor,
		removeContributor,
	} = useDispatch(store);

	const addNewRole = () => {
		addEmptyRoleAssignment();
	};

	const handleRoleSelect = (index, role) => {
		addRoleAssignment(find(roles, { id: parseInt(role) }) ?? null, index);
	};

	const handleContributorSelect = (index, contributor) => {
		addContributor(contributor, index);
	};

	const handleRemoveAssignment = (index) => {
		removeRoleAssignement(index);
	};
	const handleRemoveContributor = (id) => {
		removeContributor(id);
	};

	useEffect(() => {
		if (
			roleAssignments.length === 1 &&
			!roleAssignments[0].role &&
			!isResolving &&
			!isResolvingAuthor
		) {
			if (isSetDefaultRoleAssignementRef.current) return;

			const defaultRole_id = isInteger(mshmn_default_role)
				? mshmn_default_role
				: null;

			const defaultRoleAssignment =
				find(roles, { id: parseInt(defaultRole_id) }) ?? null;

			if (!defaultRoleAssignment || isEmpty(author)) return;

			addRoleAssignment(defaultRoleAssignment, 0);
			addContributor(author, 0);

			isSetDefaultRoleAssignementRef.current = true;
		}
	}, [mshmn_default_role, isResolving, isResolvingAuthor, isResolvingRoles]);

	return (
		<div>
			<h2>{__('Assign Contributors to Roles', 'musahimoun')}</h2>

			{roleAssignments?.map((assignment, index) => (
				<div
					key={uniqueId(`role-assignment-${index}`)}
					style={{ marginBottom: '20px', position: 'relative' }}
				>
					<div className="mshmn__remove-icon-btn-wrapper">
						<Button
							className="mshmn__remove-icon"
							icon="remove"
							onClick={() => handleRemoveAssignment(index)}
						/>
					</div>
					{isResolving && <Spinner className="mshmn__spinner" />}

					<RoleSelector
						optionlabel={__('--Select Role--', 'musahimoun')}
						controlLabel={__('SELECT ROLE:', 'musahimoun')}
						disabled={isResolving}
						roles={roles}
						selectedRole={assignment?.role?.id}
						onRoleSelect={(role) => handleRoleSelect(index, role)}
					/>
					{assignment?.role && (
						<ContributorSearch
							textControlLabel={__(
								'SEARCH CONTRIBUTOR:',
								'musahimoun'
							)}
							selectControlLabel={__(
								'SELECT CONTRIBUTOR:',
								'musahimoun'
							)}
							placeholder={__('Search...', 'musahimoun')}
							disabled={isResolving}
							index={index}
							selectedContributorIds={map(
								assignment?.contributors,
								'id'
							)}
							onContributorSelect={(contributor) =>
								handleContributorSelect(index, contributor)
							}
						/>
					)}
					<SelectedContributorsList
						title={__('SELECTED CONTRIBUTORS:', 'musahimoun')}
						onRemove={handleRemoveContributor}
						contributors={assignment?.contributors}
					/>
				</div>
			))}

			<Button
				onClick={addNewRole}
				variant="primary"
				text={__('Add Another Role', 'musahimoun')}
			/>
		</div>
	);
}

export default RoleAssignment;
