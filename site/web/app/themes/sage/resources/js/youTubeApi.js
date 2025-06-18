export function ultimosVideosSubidos() {
  const apiKey = window.jsData.ytKey;
  const espacioSutilChannelID = 'UUBHahOd3MSYL2ONV17F1XGQ';
  const videosContainer = document.getElementById('ultimos-videos');

  function loadVideos() {
    fetch(
      `https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&key=${apiKey}&playlistId=${espacioSutilChannelID}&maxResults=4&order=date`,
    )
      .then((result) => {
        return result.json();
      })
      .then((data) => {
        const ultimosVideos = data.items;

        for (const video of ultimosVideos) {
          videosContainer.innerHTML += `
          <a href="https://www.youtube.com/watch?v=${video.snippet.resourceId.videoId}" target="_blank" class="w-full rounded first:pl-none sm:ml-2 md:ml-4 first:pl-0 mb-4">
            <img class="w-full" src="http://img.youtube.com/vi/${video.snippet.resourceId.videoId}/mqdefault.jpg">
            <h3 class="p-2 bg-blanco/10">${video.snippet.title}</h3>
          </a>
          `;
        }
      });
  }

  loadVideos();
}
