import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';

const useBlockSelectionWatcher = () => {
	const [blockName, setBlockName] = useState('');
	// Get the currently selected block clientId
	const selectedBlockClientId = useSelect(
		(select) => select(blockEditorStore).getSelectedBlockClientId(),
		[]
	);

	// Get the full block object if needed
	const selectedBlock = useSelect(
		(select) => select(blockEditorStore).getSelectedBlock(),
		[selectedBlockClientId]
	);

	useEffect(() => {
		if (selectedBlock) {
			setBlockName(selectedBlock.name);
			console.log('Block selected:', selectedBlock.name, selectedBlock);
		} else {
			setBlockName('');
			console.log('No block selected');
		}
	}, [selectedBlock]);

	return blockName;
};

export default useBlockSelectionWatcher;
