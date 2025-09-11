import { useEffect } from '@wordpress/element';

function useScrollToElementOnChange(value, elementRef, targetSelector = null) {
	useEffect(() => {
		if (value !== '' && elementRef.current) {
			// Find the first scrollable parent or the window
			const scrollableParent = findScrollableParent(elementRef.current);

			if (scrollableParent) {
				if (targetSelector) {
					// If a target selector is provided, scroll to the target element
					const targetElement =
						document.querySelector(targetSelector);
					if (targetElement) {
						const extraSpace = 100; // Extra space at the top

						if (scrollableParent === window) {
							const targetRect =
								targetElement.getBoundingClientRect();
							window.scrollTo({
								top:
									window.scrollY +
									targetRect.top -
									extraSpace,
								behavior: 'smooth',
							});
						} else {
							const parentRect =
								scrollableParent.getBoundingClientRect();
							const targetRect =
								targetElement.getBoundingClientRect();

							scrollableParent.scrollTo({
								top:
									scrollableParent.scrollTop +
									targetRect.top -
									parentRect.top -
									extraSpace,
								behavior: 'smooth',
							});
						}
					}
				} else {
					// If no target selector is provided, scroll to the bottom of the parent or window
					if (scrollableParent === window) {
						window.scrollTo({
							top:
								document.documentElement.scrollHeight -
								extraSpace,
							behavior: 'smooth',
						});
					} else {
						scrollableParent.scrollTo({
							top: scrollableParent.scrollHeight - extraSpace,
							behavior: 'smooth',
						});
					}
				}
			}
		}
	}, [value, elementRef, targetSelector]);
}

// Function to find the first scrollable parent or return window
function findScrollableParent(element) {
	let parent = element.parentElement;
	while (parent) {
		const overflowY = window.getComputedStyle(parent).overflowY;
		if (overflowY === 'auto' || overflowY === 'scroll') {
			return parent;
		}
		parent = parent.parentElement;
	}
	// If no scrollable parent is found, return window
	return window;
}

export default useScrollToElementOnChange;
