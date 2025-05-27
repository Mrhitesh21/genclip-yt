function previewVideo() {
  const url = document.getElementById("youtubeUrl").value;
  const videoId = new URLSearchParams(new URL(url).search).get("v");

  if (!videoId) return alert("Invalid YouTube URL");

  document.getElementById("videoPreview").innerHTML = `
    <iframe width="100%" height="360" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>
  `;
}

function generateClips() {
  const url = document.getElementById("youtubeUrl").value;
  if (!url) return alert("Please enter a YouTube URL");

  document.getElementById("progressBar").innerHTML = `<div id="progressFill"></div>`;

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "generate_clips.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onprogress = function () {
    document.getElementById("progressFill").style.width = "50%";
  };

  xhr.onload = function () {
    document.getElementById("progressFill").style.width = "100%";

    let data;
    try {
      data = JSON.parse(this.responseText);
    } catch (e) {
      alert('Error parsing response');
      return;
    }

    if (data.error) {
      alert(data.error);
      return;
    }

    const container = document.getElementById("clipsContainer");
    container.innerHTML = "";

    data.clips.forEach((clip, index) => {
      container.innerHTML += `
        <div class="clip">
          <p>Clip ${index + 1}: ${Math.floor(clip.start)}s - ${Math.floor(clip.end)}s</p>
          <video controls width="100%" src="${clip.url}"></video>
          <br>
          <a href="${clip.url}" download>Download Clip</a>
        </div>
      `;
    });
  };

  xhr.onerror = function () {
    alert("Error generating clips.");
  };

  xhr.send("url=" + encodeURIComponent(url));
}
