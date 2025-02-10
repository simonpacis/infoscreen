import './bootstrap';

import Alpine from 'alpinejs';
import { mount } from 'svelte';

window.Alpine = Alpine;

Alpine.start();

import Elements from '../views/unit/Elements.svelte';

const elements = document.getElementById('app-elements');

if (elements) {
	const props = JSON.parse(elements.dataset.props);
	const app = mount(Elements, {
		target: elements,
			props: {
				database_elements: props.elements,
				unit_id : props.unit_id,
				location_id: props.location_id
			} 
	});
}
