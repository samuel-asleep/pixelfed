<template>
    <div>
        <div v-if="status.sensitive == true" class="content-label-wrapper">
            <div class="text-light content-label">
                <p class="text-center">
                    <i class="far fa-eye-slash fa-2x"></i>
                </p>
                <p class="h4 font-weight-bold text-center">
                    Sensitive Content
                </p>
                <p class="text-center py-2 content-label-text">
                    {{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.'}}
                </p>
                <p class="mb-0">
                    <button @click="toggleContentWarning" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Post</button>
                </p>
            </div>
        </div>

        <template v-else>
            <div v-if="!shouldPlay" class="content-label-wrapper" :style="{ background: `linear-gradient(rgba(0, 0, 0, 0.2),rgba(0, 0, 0, 0.8)),url(${getPoster(status)})`, backgroundSize: 'cover'}">
                <div class="text-light content-label">
                    <p class="mb-0">
                        <button @click.prevent="handleShouldPlay" class="btn btn-link btn-block btn-sm font-weight-bold">
                            <i class="fas fa-play fa-5x text-white"></i>
                        </button>
                    </p>
                    <p class="text-white mt-3">
                        <i class="fas fa-vr-cardboard"></i> 360° Video
                    </p>
                </div>
            </div>

            <div v-else class="video-360-container">
                <div
                    v-if="!playerInitialized"
                    class="video-360-loading"
                    :style="{ backgroundImage: `url(${getPoster(status)})` }">
                    <div class="loading-overlay">
                        <i class="fas fa-spinner fa-spin fa-3x"></i>
                        <p class="mt-3">Loading 360° Video...</p>
                    </div>
                </div>
                <video
                    ref="video360"
                    class="video-js vjs-default-skin video-360-player"
                    :class="{ 'player-ready': playerInitialized }"
                    playsinline
                    crossorigin="anonymous">
                </video>
            </div>
        </template>
    </div>
</template>

<script>
export default {
    props: ['status', 'fixedHeight'],

    data() {
        return {
            shouldPlay: false,
            player: null,
            playerInitialized: false,
        }
    },

    beforeDestroy() {
        if (this.player) {
            this.player.dispose();
            this.player = null;
        }
    },

    methods: {
        toggleContentWarning() {
            this.status.sensitive = false;
        },

        handleShouldPlay() {
            this.shouldPlay = true;
            this.$nextTick(() => {
                this.initPlayer();
            });
        },

        getPoster(status) {
            let attachment = status.media_attachments[0];
            if (attachment.preview_url) {
                return attachment.preview_url;
            }
            return '/storage/no-preview.png';
        },

        async initPlayer() {
            try {
                // Check if WebGL is supported
                if (!this.isWebGLSupported()) {
                    console.warn('WebGL not supported, falling back to regular video');
                    this.showFallback();
                    return;
                }

                // Dynamically import video.js and videojs-vr
                const videojs = (await import('video.js')).default;
                await import('videojs-vr');

                const videoElement = this.$refs.video360;
                const attachment = this.status.media_attachments[0];

                // Initialize Video.js player
                this.player = videojs(videoElement, {
                    controls: true,
                    autoplay: false,
                    preload: 'auto',
                    poster: this.getPoster(this.status),
                    fluid: false,
                    width: '100%',
                    height: 400,
                    sources: [{
                        src: attachment.hls_manifest || attachment.url,
                        type: attachment.hls_manifest ? 'application/x-mpegURL' : attachment.mime
                    }]
                });

                // Initialize VR plugin for 360 video
                this.player.vr({
                    projection: '360',
                    debug: false,
                    forceCardboard: false
                });

                this.player.ready(() => {
                    this.playerInitialized = true;
                });

                this.player.on('error', (err) => {
                    console.error('Video.js error:', err);
                    this.showFallback();
                });

            } catch (error) {
                console.error('Failed to load 360 video player:', error);
                this.showFallback();
            }
        },

        isWebGLSupported() {
            try {
                const canvas = document.createElement('canvas');
                return !!(window.WebGLRenderingContext && 
                    (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
            } catch (e) {
                return false;
            }
        },

        showFallback() {
            // Fall back to regular HTML5 video
            const attachment = this.status.media_attachments[0];
            const video = document.createElement('video');
            video.src = attachment.url;
            video.poster = this.getPoster(this.status);
            video.controls = true;
            video.autoplay = false;
            video.className = 'card-img-top';
            video.style.width = '100%';
            video.style.maxHeight = '400px';
            video.style.backgroundColor = '#000';
            
            if (this.$refs.video360) {
                this.$refs.video360.parentNode.replaceChild(video, this.$refs.video360);
            }
            this.playerInitialized = true;
        }
    }
};
</script>

<style scoped>
.video-360-container {
    position: relative;
    width: 100%;
    min-height: 400px;
    background: #000;
}

.video-360-player {
    width: 100%;
    height: 400px;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.video-360-player.player-ready {
    opacity: 1;
}

.video-360-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.loading-overlay {
    background: rgba(0, 0, 0, 0.7);
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    color: #fff;
}

.content-label-wrapper {
    position: relative;
    min-height: 400px;
    background-size: cover;
    background-position: center;
}

.content-label {
    margin: 0;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    z-index: 2;
    background: rgba(0, 0, 0, 0.2);
}
</style>
