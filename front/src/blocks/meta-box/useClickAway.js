import { useRef, useEffect } from '@wordpress/element';

const useClickAway = (setToggle) => {
	const dropdownRef = useRef(null);

	const handleClickOutside = (event) => {
		// Check if the click was outside the dropdown element
		if (
			dropdownRef.current &&
			!dropdownRef.current.contains(event.target)
		) {
			setToggle(false);
		}
	};

	useEffect(() => {
		// Bind the event listener when the component is mounted
		document.addEventListener('mousedown', handleClickOutside);

		// Unbind the event listener when the component is unmounted
		return () => {
			document.removeEventListener('mousedown', handleClickOutside);
		};
	}, []);

	return dropdownRef;
};

export default useClickAway;
