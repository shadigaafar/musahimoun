import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function RoleSelector({
	optionlabel,
	controlLabel,
	disabled,
	roles,
	onRoleSelect,
	selectedRole,
}) {
	return (
		<SelectControl
			disabled={disabled}
			label={controlLabel}
			value={selectedRole ?? ''}
			options={[
				{ label: optionlabel, value: '' },
				...roles?.map((role) => ({
					label: role?.name,
					value: role?.id,
				})),
			]}
			onChange={onRoleSelect}
		/>
	);
}

export default RoleSelector;
