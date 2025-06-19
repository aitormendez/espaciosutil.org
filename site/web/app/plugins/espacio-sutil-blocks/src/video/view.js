/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

// site/web/app/plugins/fb-blocks/src/video/view.js
// -------------------------------------------------

/* 1 · Estilos (elimínalos si ya los traes en tu Sass) */
import "vidstack/player/styles/default/theme.css";
import "vidstack/player/styles/default/layouts/video.css";

/* 2 · Registra todos los custom‑elements */
import "vidstack/player";
import "vidstack/player/layouts/default";

/* 3 · Librería HLS */
import Hls from "hls.js";

document.addEventListener("DOMContentLoaded", () => {
	document.querySelectorAll(".fb-video-player").forEach((root, idx) => {
		const player = document.createElement("media-player");
		const provider = document.createElement("media-provider");
		const layout = document.createElement("media-video-layout");

		player.setAttribute(
			"src",
			root.dataset.videosrc ??
				"https://files.vidstack.io/sprite-fight/hls/stream.m3u8",
		);

		player.setAttribute(
			"poster",
			root.dataset.poster ??
				"https://files.vidstack.io/sprite-fight/poster.webp",
		);

		player.setAttribute("title", `Video ${idx + 1}`);
		player.setAttribute("layout", "video");

		if (root.dataset.controls === "true") player.setAttribute("controls", "");

		if (root.dataset.playsinline !== "false")
			player.setAttribute("playsinline", "");

		if (root.dataset.autoplay === "true") {
			player.setAttribute("autoplay", "");
			player.setAttribute("muted", "");
		}
		if (root.dataset.muted === "true") {
			player.setAttribute("muted", "");
		}

		if (root.dataset.loop === "true") {
			player.setAttribute("loop", "");
		}

		player.setAttribute("load", "eager");
		player.setAttribute("crossorigin", "anonymous");

		player.appendChild(provider);
		if (root.dataset.controls !== "false") {
			player.appendChild(layout);
		}
		root.replaceChildren(player);

		player.addEventListener("loaded-metadata", (event) => {
			const videoEl = player.querySelector("video");

			if (!videoEl) return;

			const w = videoEl.videoWidth;
			const h = videoEl.videoHeight;

			if (w && h) {
				const ratio = (w / h).toFixed(2); // ej. 1.78 para 16/9
				console.log(`📐 Video aspect ratio: ${w}:${h} = ${ratio}`);

				// Aplica el aspecto dinámico al contenedor
				root.style.aspectRatio = `${w} / ${h}`;
			}
		});

		player.addEventListener("provider-change", ({ detail }) => {
			if (detail?.type === "hls" && !detail.library) {
				detail.library = Hls;
			}
		});

		player.addEventListener("hls-lib-load-start", () =>
			console.log("⏳ hls.js descargándose…"),
		);
		player.addEventListener("hls-lib-loaded", () =>
			console.log("✅ hls.js cargada"),
		);
		player.addEventListener("error", (e) =>
			console.error("❌ player error", e.detail),
		);
	});
});
