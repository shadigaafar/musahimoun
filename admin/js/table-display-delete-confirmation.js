document.addEventListener('DOMContentLoaded', () => {
	const translations = mshmnListTableTranslation; // or your correct var name

	const applyConfirmation = () => {
		const deleteLinks = document.querySelectorAll('.row-actions .delete a');
		deleteLinks.forEach((link) => {
			// Avoid duplicating the attribute
			if (!link.hasAttribute('data-confirm-attached')) {
				link.setAttribute(
					'onclick',
					`return confirm("${translations.confirmationMessage}")`
				);
				link.setAttribute('data-confirm-attached', 'true');
			}
		});
	};

	// Initial run
	applyConfirmation();

	// Observe future changes
	const observer = new MutationObserver(() => {
		applyConfirmation();
	});

	observer.observe(document.body, { childList: true, subtree: true });
});
