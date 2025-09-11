import { useState, useEffect } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { isEmpty, find, isNumber } from 'lodash';
import useClickAway from './useClickAway';
import useScrollToElementOnChange from './useScrollToElementOnChange';
import { TextControl } from '@wordpress/components';

function ContributorSearch({
	selectedContributorIds,
	onContributorSelect,
	disabled,
	index,
	textControlLabel,
	selectControlLabel,
	placeholder,
}) {
	const [contributors, setContributors] = useState([]);
	const [searchTerm, setSearchTerm] = useState('');
	const [toggle, setToggle] = useState(false);
	const dropdownRef = useClickAway(setToggle);

	useScrollToElementOnChange(
		toggle,
		dropdownRef,
		`#musahimoun-select-contributor-${index}`
	);

	useEffect(() => {
		// Fetch contributors from REST API.
		if (!isEmpty(searchTerm) && searchTerm.length > 1) {
			apiFetch({
				path: `/mshmn/v1/contributors?search=${searchTerm}`,
				method: 'get',
			})
				.then((data) => {
					if (!isEmpty(data)) {
						setToggle(true);
						setContributors(data);
					}
				})
				.catch((error) =>
					console.error(
						__(
							'contributorSearch component: Error fetching contributors:',
							'musahimoun'
						),
						error
					)
				);
		}
	}, [searchTerm]);

	const handleOnChangeInput = (text) => {
		setSearchTerm(text);
	};
	const handleOnChangeSelect = (selectedContributorId) => {
		if (isNumber(parseInt(selectedContributorId))) {
			onContributorSelect(
				find(contributors, { id: parseInt(selectedContributorId) })
			);
		}
		setToggle(false);
		setSearchTerm('');
	};
	const filteredContributors = contributors?.filter((contributor) =>
		contributor.name.toLowerCase().includes(searchTerm.toLowerCase())
	);

	const options = filteredContributors?.map((contributor) => ({
		label: contributor.name,
		value: contributor.id,
	}));

	return (
		<div
			className="mshmn__search-contributors"
			style={{ maxWidth: 400 }}
			ref={dropdownRef}
		>
			<TextControl
				disabled={disabled}
				placeholder={placeholder}
				value={searchTerm}
				onChange={handleOnChangeInput}
				label={textControlLabel}
			/>
			{toggle && (
				<SelectControl
					id={`musahimoun-select-contributor-${index}`}
					label={selectControlLabel}
					multiple
					value={
						!isEmpty(selectedContributorIds)
							? selectedContributorIds
							: []
					}
					options={options}
					onChange={handleOnChangeSelect}
				/>
			)}
		</div>
	);
}

export default ContributorSearch;
