import { isEmpty, uniqueId } from 'lodash';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const SelectedContributorsList = ({ title, contributors, onRemove }) => {
	const handleRemoveContributor = (id) => {
		onRemove?.(id);
	};
	if (isEmpty(contributors)) return;

	return (
		<>
			<h3>{title}</h3>
			<ul className="mshmn__selected-contributors-list">
				{contributors.map((contributor) => (
					<li key={uniqueId('contributor-') + contributor?.id}>
						{contributor?.name}
						<Button
							className="mshmn__remove-icon"
							icon="remove"
							size="small"
							onClick={() =>
								handleRemoveContributor(contributor?.id)
							}
						/>
					</li>
				))}
			</ul>
		</>
	);
};
export default SelectedContributorsList;
