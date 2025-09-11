import { createRoot } from 'react-dom/client';
import App from './app';
import './style.css';
import domReady from '@wordpress/dom-ready';

domReady(function () {
	const root = createRoot(document.getElementById('contributor_meta_box'));
	root.render(<App />);
});
