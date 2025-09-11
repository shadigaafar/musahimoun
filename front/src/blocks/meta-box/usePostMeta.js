import { useEffect, useState, useRef, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { useSelect, useDispatch } from '@wordpress/data';

function usePostMeta(postType, postId) {
	const [metaValues, setMetaValues] = useState();
	const [isSaving, setIsSaving] = useState(false);
	const metaSaveSuccess = useRef(true); // Track the success of all meta saves
	const dispatch = useDispatch();

	const isSavingPost = useSelect(
		(select) => select('core/editor').isSavingPost(),
		[]
	);

	const isPostSaved = useSelect(
		(select) => select('core/editor').didPostSaveRequestSucceed(),
		[]
	);

	// Fetch all meta values when the hook is initialized
	useEffect(() => {
		if (!postId) return;

		const fetchMetaValues = async () => {
			try {
				const response = await apiFetch({
					path: `/wp/v2/posts/${postId}`,
					method: 'GET',
				});
				console.log(response);
				if (response?.meta) {
					setMetaValues((prevValues) => ({
						...prevValues,
						...response.meta,
					}));
				}
			} catch (error) {
				console.error('Error fetching meta values:', error);
			}
		};

		fetchMetaValues();
	}, [postId]);

	// Attempt to save all meta values before the post is saved
	useEffect(() => {
		const saveMetaValues = async () => {
			if (!isSavingPost || isSaving || !Object.keys(metaValues).length)
				return;

			setIsSaving(true);
			metaSaveSuccess.current = true; // Reset success tracker before saving

			try {
				await apiFetch({
					path: `/wp/v2/posts/${postId}`,
					method: 'put',
					data: {
						meta: metaValues,
					},
				});
			} catch (error) {
				console.error('Error saving meta values:', error);
				metaSaveSuccess.current = false;
			} finally {
				setIsSaving(false);
			}
		};

		saveMetaValues();
	}, [isSavingPost, metaValues, postId]);

	// Intercept the post save action if any meta save fails
	useEffect(() => {
		if (!isSavingPost || metaSaveSuccess.current) return;

		// Prevent post save if any meta save failed
		dispatch('core/editor').lockPostSaving('metaSaveFailure');
	}, [isSavingPost, metaSaveSuccess.current]);

	// Unlock post saving if all meta saves were successful
	useEffect(() => {
		if (metaSaveSuccess.current) {
			dispatch('core/editor').unlockPostSaving('metaSaveFailure');
		}
	}, [isPostSaved]);

	// Update specific meta field's value
	const updateMetaValue = useCallback(
		(metaKey, newValue) => {
			setMetaValues((prevValues) => ({
				...prevValues,
				[metaKey]: newValue,
			}));
		},
		[setMetaValues]
	);

	return [metaValues, updateMetaValue, isSaving];
}

export default usePostMeta;
