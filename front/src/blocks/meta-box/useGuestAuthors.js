import { useState, useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';

const useGuestAuthors = (allPostAuthorIds) => {
	const [allGuestAuthors, setAllGuestAuthors] = useState([]);
	const [postGuestAuthors, setPostGuestAuthors] = useState([]);

	const isMounted = useRef(true);

	useEffect(() => {
		const fetchData = async () => {
			if (isMounted.current) {
				try {
					const res = await apiFetch({
						path: '/mshmn/v1/guest-authors',
						method: 'get',
					});

					console.log('res', res);
					const _guestAuthors =
						res.length > 0 && res[0] !== null ? res : [];
					const _postGuestAuthors = _guestAuthors.filter((entity) =>
						allPostAuthorIds.includes(entity?.id)
					);

					setAllGuestAuthors(_guestAuthors);
					setPostGuestAuthors(_postGuestAuthors);
				} catch (err) {
					console.error(err.response);
				}
			}
		};

		fetchData();

		return () => {
			if (postGuestAuthors.length > 0) isMounted.current = false;
		};
	}, [allPostAuthorIds]);

	return { allGuestAuthors, postGuestAuthors, setPostGuestAuthors };
};

export default useGuestAuthors;
