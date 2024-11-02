<template>
    <section class="bg-video-section">
      <!-- Background Video -->
      <video class="bg-video" autoplay muted loop playsinline>
        <source src="/videos/globe.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>

      <!-- Overlays -->
      <div class="bg-video-overlay"></div> <!-- Main Overlay -->
      <div class="bg-video-blur"></div> <!-- Right-side blur overlay -->

      <!-- Right-aligned Content -->
      <div class="content-wrapper">
        <h1 class="section-title">Project Night Vision</h1>
        <h2 class="section-subtitle">
          <p>Dreams are more than isolated experiences — they are data points in a vast, shared network of human thought. Project Night Vision seeks to collect and analyze these fragments, mapping patterns that could reveal insights into the collective unconscious.</p>
          <p>With every dream we gather, the contours of a deeper, shared consciousness become clearer.</p>
          <p>Explore emerging patterns in Global Data, study narratives in the Dream Library, or contribute your own dream to expand the research.</p>
        </h2>
        <ul class="link-list">
          <li><a href="/dream-globe">Global Data</a></li>
          <li><a href="/library">Dream Library</a></li>
          <li><a href="/submit-dream">Submit a Dream</a></li>
        </ul>
      </div>
    </section>
  </template>

  <script>
  export default {};
  </script>

  <style scoped>
  /* Section Container */
  .bg-video-section {
    position: relative;
    width: 100%;
    height: 100vh;
    overflow: hidden;
    display: flex;
    justify-content: flex-end;
    align-items: center;
  }

  /* Background Video */
  .bg-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 1;
  }

  /* Main Overlay covering the entire section */
  .bg-video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(0, 0, 0, 0.5) 70%, rgba(0, 0, 0, 0.2));
    z-index: 2;
  }

  /* Right-side blur overlay */
  .bg-video-blur {
    position: absolute;
    top: 0;
    right: 0;
    width: 80%; /* Adjust as needed to create the right-side blur */
    height: 100%;
    z-index: 3;
    pointer-events: none; /* Prevents interference with user interaction */

    /* Apply the blur effect */
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);

    /* Gradient mask for smooth fade-in of the blur */
    mask-image: linear-gradient(to left, rgba(0, 0, 0, 1) 20%, rgba(0, 0, 0, 0) 100%);
    -webkit-mask-image: linear-gradient(to left, rgba(0, 0, 0, 1) 20%, rgba(0, 0, 0, 0) 100%);
  }

  /* Content Wrapper for Text */
  .content-wrapper {
    position: relative;
    z-index: 4; /* Ensure content stays on top */
    text-align: right;
    color: white;
    padding: 20px;
    max-width: 500px;
    margin-right: 20px;
    box-sizing: border-box;
  }

  .content-wrapper * {
    font-family: "Mate SC", serif;
    font-weight: 400;
    font-style: normal;
  }

  .section-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #fff;
  }

  .section-subtitle * {
    font-size: 14px;
    margin-bottom: 1.5rem;
    color: #fff;
  }

  .link-list {
    list-style: none;
    padding: 0;
  }

  .link-list li {
    margin-bottom: 0.5rem;
  }

  .link-list a {
    color: white;
    text-decoration: none;
    font-weight: bold;
  }

  .link-list a:hover {
    text-decoration: underline;
  }
  </style>
