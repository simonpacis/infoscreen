<script>
import { FFmpeg } from '@ffmpeg/ffmpeg';
import { fetchFile, toBlobURL } from '@ffmpeg/util';

let videoElement = $state();
let loaded = $state(false);
let ffmpeg = new FFmpeg();

async function load() {
	const baseURL = '/js';
	ffmpeg.on('log', ({ message }) => {
		if (messageElement) messageElement.innerHTML = message;
		console.log(message);
	});
	await ffmpeg.load({
		coreURL: await toBlobURL(`${baseURL}/ffmpeg-core.js`, 'text/javascript'),
		wasmURL: await toBlobURL(`${baseURL}/ffmpeg-core.wasm`, 'application/wasm'),
	});
	loaded.set(true);
}

load();

async function transcode() {
	await load();
	await ffmpeg.writeFile('input.webm', await fetchFile('http://127.0.0.1:8000/downloadwebm'));
	await ffmpeg.exec(['-i', 'input.webm', 'output.mp4']);
	const data = await ffmpeg.readFile('output.mp4');
	if (videoElement) {
		videoElement.src = URL.createObjectURL(new Blob([data.buffer], { type: 'video/mp4' }));
	}
}

</script>

<video bind:this={videoElement} controls></video><br/>
<button onclick={transcode}>Transcode webm to mp4</button>
<p>Open Developer Tools (Ctrl+Shift+I) to View Logs</p>
