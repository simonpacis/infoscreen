<script>
import Sortable from 'sortablejs';
import { onMount } from 'svelte';
let { database_elements, unit_id, location_id } = $props();
let elements = $state([]);
let sortable = $state(false);
let sortlist = $state();
let files = $state();

async function wrapFile(file) {
	let filetype = file.type.startsWith("video/") ? "video" : "image";

	return {
		id: crypto.randomUUID(),
		name: file.name,
		file: file,
		duration: 5,
		location: "memory",
		type: filetype,
		uploading: false,
		can_delete: true,
		uploaded: false,
		dataUrl: URL.createObjectURL(file)
	}
}

onMount(() => {
	elements = database_elements.map(element => {
		return {
			id: element.generated_id,
			name: element.name,
			file: element.url,
			filepath: element.filepath,
			duration: element.duration,
			location: "database",
			type: element.type,
			dataUrl: element.url,
			uploading: false,
			uploaded: false,
			can_delete: true,
			order: element.order
		}
	})
	elements = elements.sort((a, b) => a.order - b.order);
})


$effect(async () => {

	if (files?.length > 0) {
		const add_files = await Promise.all(Array.from(files).map(wrapFile));
		const maxOrder = elements.reduce((max, el) => Math.max(max, el.order), -1);
		add_files.forEach((el, index) => {
			el.order = maxOrder + index + 1
		});
		elements = [...elements, ...add_files];
		elements = elements.sort((a, b) => a.order - b.order);
		files = null;
	}

    if (elements.length > 0 && elements.every(el => el.uploaded)) {
        window.location.href = '/location/'+location_id; // Change to your target URL
    }

	if(elements.length > 0 && !sortable)
	{
		sortable = true;
		Sortable.create(sortlist, {
			group: {
				name: 'sortlist',
			},
			onEnd: updateOrder,

			animation: 200,
		});
	}


});


function updateOrder(evt) {
	const orderedIds = Array.from(evt.to.children).map(el => el.getAttribute("data-id"));
	elements.forEach((el, index) => {
		const newIndex = orderedIds.indexOf(el.id);
		if (newIndex !== -1) {
			el.order = newIndex;  // Update order starting from 0
		}
	});
}

async function upload() {

	const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
	let element_list = new FormData();
	element_list.append('unit_id', unit_id);
	element_list.append('element_list', JSON.stringify(elements.map(el => {
		return {
			generated_id: el.id
		}
	})));
	const response = await fetch('/purge', {
		method: 'POST',
		headers: {
			'X-CSRF-TOKEN': csrfToken,  // Include CSRF token in the request headers
		},
		body: element_list 
	});

	// Loop through each element and send one by one
	elements.forEach(async (element) => {
		let formData = new FormData();
		const file = element.file;

		// Append the renamed file and metadata
		let newFileName;
		let extension;
		if(element.location == "memory")
		{
			extension = file.name.split('.').pop();
			newFileName = `${element.id}.${extension}`;
			formData.append('file', file, newFileName);
		} else {
			newFileName = element.filepath;
		}
		formData.append('metadata', JSON.stringify({
			generated_id: element.id,
			name: element.name,
			duration: element.duration,
			location: element.location,
			order: element.order,
			type: element.type,
			filename: newFileName,
			unit_id: unit_id
		}));

		// Send the file and metadata
		element.uploading = true;
		const response = await fetch('/upload', {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': csrfToken,  // Include CSRF token in the request headers
			},
			body: formData
		});

		if (response.ok) {
			element.uploading = false;
			element.uploaded = true;
			console.log(`File ${newFileName} uploaded successfully`);
		} else {
			console.error('Error uploading file');
		}
	});
}
</script>
<div class="flex">
	<label for="fileInput" class="ml-3 bg-gray-100 font-bold cursor-pointer hover:bg-gray-300  w-auto h-12 mb-6 border-2 border-gray-500 rounded-lg px-4 inline-flex items-center justify-center">
		Tilføj fil
		<input bind:files={files} id="fileInput" multiple type="file" class="hidden" accept=".jpg,.jpeg,.png,.mkv,.mov,.mp4,.m4v,.mk3d,.wmv,.asf,.mxf,.ts,.m2ts,.3gp,.3g2,.flv,.webm,.ogv,.rmvb,.avi" />
	</label>
	{#if elements.length > 0}
		<button class="ml-3 bg-gray-100 font-bold cursor-pointer hover:bg-gray-300  w-auto h-12 mb-6 border-2 border-gray-500 rounded-lg px-4 inline-flex items-center justify-center" onclick={upload}>Gem infoskærm</button>
	{/if}
	<a  href="/unit/{unit_id}/settings" class="ml-auto ml-3 bg-gray-100 font-bold cursor-pointer hover:bg-gray-300  w-auto h-12 mb-6 border-2 border-gray-500 rounded-lg px-4 inline-flex items-center justify-center">Indstillinger</a>
</div>

{#if elements.length > 1}
<div class="flex mb-3">
	<p>Træk og slip elementerne for at ændre deres rækkefølge. Uploader du en videofil vil lyden ikke blive afspillet på infoskærmen.</p>
</div>
{/if}

<section bind:this={sortlist} >
	{#each elements as element}
		<div class="border border-gray-400 my-2 rounded-xl bg-white hover:bg-gray-100 cursor-pointer flex w-full items-center py-4 px-4" data-id={element.id}>
			{#if element.type == "image"}
				<img src={element.dataUrl} alt={element.name} class="border border-orange-400 rounded-xl h-32 w-auto" />
			{:else}
				<video src={element.dataUrl} controls class="border border-orange-400 rounded-xl h-32 w-auto"></video>
			{/if}
			<p class="mx-4 font-bold">{element.name}</p>
			{#if element.type == "image"}
				<div class="ml-6 flex items-center justify-center">
					<p>Varighed (i sekunder)</p>
					<input placeholder="Antal sekunder skal vises i" type="number" min="0" max="100" bind:value={element.duration} class="mx-2 rounded-xl" />
				</div>
			{/if}
			{#if element.uploading}
				<div class="ml-6 flex flex-col items-center justify-center">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 24 24"><script xmlns=""/><style>.spinner_ajPY{transform-origin:center;animation:spinner_AtaB .75s infinite linear}@keyframes spinner_AtaB{100%{transform:rotate(360deg)}}</style><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" class="spinner_ajPY"/></svg>
						<p class="mt-2 text-sm text-gray-400">(Uploading...)</p>
				</div>
			{/if}
			<button class="h-8 w-8 p-2 border font-bold border-red-400 bg-red-100 hover:bg-red-400 rounded-xl text-red-400 ml-auto hover:text-white flex items-center justify-center" onclick={() => elements = elements.filter(f => f.name != element.name)}>X</button>
		</div>
	{/each}
</section>
