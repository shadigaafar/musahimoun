import RoleSelector from './roleSelector';
import ContributorSearch from './contributorSearch';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { find, map } from 'lodash';
import SelectedContributorsList from './selectedContributorsList';
import { store } from './../../store/index';
import { useDispatch, useSelect, select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

function RoleAssignment() {
	const record = select(editorStore).getCurrentPost();
	const postId = record?.id ?? null;
	const { roles, roleAssignments, isResolving } = useSelect(
		(select) => {
			const selectors = select(store);
			if (!selectors) {
				console.error('Mushaimoun: store problem, selectors is empty!');
			}
			return {
				roles: selectors?.getRoles(),
				roleAssignments: selectors?.getRoleAssignments(),
				isResolving: selectors?.isResolving('getRoleAssignments'),
			};
		},
		[record]
	);

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

	return (
		<div>
			<h2>{__('Assign Contributors to Roles', 'musahimoun')}</h2>

			{roleAssignments?.map((assignment, index) => (
				<div
					key={index}
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
