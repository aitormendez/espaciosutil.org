import { useEffect } from "react";
import { __ } from "@wordpress/i18n";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";
import "@vidstack/react/player/styles/default/theme.css";
import "@vidstack/react/player/styles/default/layouts/video.css";
import { MediaPlayer, MediaProvider } from "@vidstack/react";
import {
	defaultLayoutIcons,
	DefaultVideoLayout,
} from "@vidstack/react/player/layouts/default";

export default function Edit({ attributes, setAttributes }) {
	const {
		libraryId,
		videoId,
		videoSrc,
		thumbnailUrl,
		autoplay,
		loop,
		muted,
		controls,
		playsInline,
	} = attributes;

	const blockProps = useBlockProps();

	useEffect(() => {
		if (!videoId || !libraryId) return;

		const controller = new AbortController();

		async function fetchVideoData() {
			try {
				const response = await fetch(
					`/wp-json/espacio-sutil/v1/video-resolutions?library_id=${libraryId}&video_id=${videoId}`,
					{ signal: controller.signal },
				);
				const data = await response.json();

				if (data?.hlsUrl) {
					setAttributes({
						videoSrc: data.hlsUrl,
						thumbnailUrl: data.thumbnailUrl ?? thumbnailUrl,
					});
				}
			} catch (err) {
				if (err.name !== "AbortError") {
					console.error("Error fetching video data:", err);
				}
			}
		}

		fetchVideoData();

		return () => controller.abort();
	}, [libraryId, videoId]);

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody
					title={__("Video Settings", "blocks/video")}
					initialOpen={true}
				>
					<TextControl
						label={__("Library ID", "blocks/video")}
						value={libraryId}
						onChange={(value) => setAttributes({ libraryId: value })}
					/>
					<TextControl
						label={__("Video ID", "blocks/video")}
						value={videoId}
						onChange={(value) => setAttributes({ videoId: value })}
					/>
					<ToggleControl
						label={__("Autoplay", "blocks/video")}
						checked={autoplay}
						onChange={(value) => setAttributes({ autoplay: value })}
					/>
					<ToggleControl
						label={__("Loop", "blocks/video")}
						checked={loop}
						onChange={(value) => setAttributes({ loop: value })}
					/>
					<ToggleControl
						label={__("Muted", "blocks/video")}
						checked={muted}
						onChange={(value) => setAttributes({ muted: value })}
					/>
					<ToggleControl
						label={__("Controls", "blocks/video")}
						checked={controls}
						onChange={(value) => setAttributes({ controls: value })}
					/>
					<ToggleControl
						label={__("Plays Inline", "blocks/video")}
						checked={playsInline}
						onChange={(value) => setAttributes({ playsInline: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<MediaPlayer
				title="FB Video Preview"
				src={videoSrc}
				autoplay={autoplay}
				loop={loop}
				muted={muted}
				controls={controls}
				playsInline={playsInline}
				poster={thumbnailUrl || undefined}
			>
				<MediaProvider />
				<DefaultVideoLayout icons={defaultLayoutIcons} />
			</MediaPlayer>
		</div>
	);
}
