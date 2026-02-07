<template>
    <div v-if="status.sensitive == true" class="content-label-wrapper">
        <div class="text-light content-label">
            <p class="text-center">
                <i class="far fa-eye-slash fa-2x"></i>
            </p>
            <p class="h4 font-weight-bold text-center">
                {{ isFiltered ? 'Filtered Content' : 'Sensitive Content' }}
            </p>
            <p class="text-center py-2 content-label-text">
                {{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.' }}
            </p>
            <p class="mb-0">
                <button class="btn btn-outline-light btn-block btn-sm font-weight-bold" @click="toggleContentWarning()">See Post</button>
            </p>
        </div>
        <blur-hash-image
            width="32"
            height="32"
            :punch="1"
            :hash="status.media_attachments[0].blurhash"
            :alt="altText(status)"
        />
    </div>
    <div v-else>
        <div class="photo-360-container">
            <div
                v-if="!viewerInitialized"
                class="photo-360-loading"
                :style="{ backgroundImage: `url(${status.media_attachments[0].preview_url})` }">
                <div class="loading-overlay">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-3">Loading 360° Photo...</p>
                </div>
            </div>
            <div
                ref="panorama"
                class="photo-360-viewer"
                :class="{ 'viewer-ready': viewerInitialized }">
            </div>
            <div v-if="viewerInitialized" class="photo-360-controls">
                <div class="control-hint">
                    <i class="fas fa-hand-pointer"></i> Drag to look around
                </div>
                <button
                    class="btn btn-sm btn-light fullscreen-btn"
                    @click="toggleFullscreen"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'">
                    <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>
            </div>

            <p
                v-if="status.media_attachments[0].license"
                class="photo-license">
                <a :href="status.url" class="font-weight-bold text-light">360° Photo</a> by <a :href="status.account.url" class="font-weight-bold text-light">&commat;{{ status.account.username }}</a> licensed under <a :href="status.media_attachments[0].license.url" class="font-weight-bold text-light">{{ status.media_attachments[0].license.title }}</a>
            </p>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        status: {
            type: Object
        },
        isFiltered: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            sensitive: this.status.sensitive,
            viewer: null,
            viewerInitialized: false,
            isFullscreen: false
        };
    },

    mounted() {
        if (!this.status.sensitive) {
            this.initViewer();
        }
    },

    beforeDestroy() {
        if (this.viewer) {
            this.viewer.destroy();
            this.viewer = null;
        }
    },

    methods: {
        altText(status) {
            let desc = status.media_attachments[0].description;
            if (desc) {
                return desc;
            }
            return "360° photo was not tagged with any alt text.";
        },

        toggleContentWarning() {
            this.$emit("togglecw");
            if (!this.viewerInitialized) {
                this.$nextTick(() => {
                    this.initViewer();
                });
            }
        },

        async initViewer() {
            try {
                // Dynamically import Pannellum
                const pannellum = await import('pannellum');
                
                // Check if WebGL is supported
                if (!this.isWebGLSupported()) {
                    console.warn('WebGL not supported, falling back to regular image');
                    this.showFallback();
                    return;
                }

                const imageUrl = this.status.media_attachments[0].url;
                
                this.viewer = pannellum.viewer(this.$refs.panorama, {
                    type: "equirectangular",
                    panorama: imageUrl,
                    autoLoad: true,
                    showControls: true,
                    showFullscreenCtrl: false, // We have our own
                    showZoomCtrl: true,
                    mouseZoom: true,
                    draggable: true,
                    keyboardZoom: true,
                    hfov: 100, // Horizontal field of view
                    pitch: 0,
                    yaw: 0,
                    minHfov: 50,
                    maxHfov: 120,
                    autoRotate: 0, // Disabled by default
                });

                // Mark as initialized when panorama loads
                this.viewer.on('load', () => {
                    this.viewerInitialized = true;
                });

                this.viewer.on('error', (err) => {
                    console.error('Pannellum error:', err);
                    this.showFallback();
                });

            } catch (error) {
                console.error('Failed to load Pannellum:', error);
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
            // Show regular image if 360 viewer fails
            const img = document.createElement('img');
            img.src = this.status.media_attachments[0].url;
            img.alt = this.altText(this.status);
            img.className = 'card-img-top';
            img.style.width = '100%';
            this.$refs.panorama.innerHTML = '';
            this.$refs.panorama.appendChild(img);
            this.viewerInitialized = true;
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                this.$refs.panorama.requestFullscreen().then(() => {
                    this.isFullscreen = true;
                }).catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    this.isFullscreen = false;
                });
            }
        }
    }
};
</script>

<style scoped>
.photo-360-container {
    position: relative;
    width: 100%;
    min-height: 400px;
    background: #000;
}

.photo-360-viewer {
    width: 100%;
    height: 400px;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.photo-360-viewer.viewer-ready {
    opacity: 1;
}

.photo-360-loading {
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
}

.loading-overlay {
    background: rgba(0, 0, 0, 0.7);
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    color: #fff;
}

.photo-360-controls {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
    pointer-events: none;
}

.control-hint {
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
}

.fullscreen-btn {
    pointer-events: all;
    background: rgba(255, 255, 255, 0.9) !important;
}

.content-label-wrapper {
    position: relative;
    min-height: 400px;
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

.photo-license {
    margin-bottom: 0;
    padding: 0 5px;
    color: #fff;
    font-size: 10px;
    text-align: right;
    position: absolute;
    bottom: 0;
    right: 0;
    border-top-left-radius: 5px;
    background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
}
</style>
