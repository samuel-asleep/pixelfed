# 360° Media Support

This implementation adds support for viewing 360° photos and videos in the Pixelfed web interface.

## Features

### Browser Support
- ✅ Desktop browsers (Chrome, Firefox, Safari, Edge)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile, Firefox Mobile)
- ✅ VR headsets with WebGL-enabled browsers
- ✅ Automatic fallback to regular media display if WebGL is not supported

### Media Types
- **360° Photos**: Equirectangular projection photos with 2:1 aspect ratio
- **360° Videos**: Equirectangular projection videos in MP4 format

## Detection Methods

### XMP Metadata Detection
The system looks for the following XMP metadata tags in uploaded media:

**For Photos:**
- `GPano:ProjectionType="equirectangular"`
- `GSpherical:Spherical="true"`

**For Videos:**
- `XMP-GSpherical:Spherical="true"`
- `GPano:ProjectionType="equirectangular"`

### Aspect Ratio Fallback
If XMP metadata is not present, the system uses aspect ratio detection:
- Media with a 2:1 aspect ratio (±2.5% tolerance) is considered 360°
- This method is less reliable but catches cases where XMP metadata is missing

## Configuration

### Environment Variables

```env
# Enable/disable 360° media detection (default: true)
MEDIA_360_DETECTION=true
```

### Adding XMP Metadata to Media

You can mark your media as 360° using exiftool:

**For Photos:**
```bash
exiftool -XMP-GPano:ProjectionType="equirectangular" photo.jpg
```

**For Videos:**
```bash
exiftool -XMP-GSpherical:Spherical="true" video.mp4
```

## User Experience

### 360° Photos
- Interactive pan/tilt/zoom controls
- Mouse drag to look around
- Mouse wheel to zoom in/out
- Fullscreen mode available
- Mobile: Touch gestures for navigation
- VR: Head tracking support (when viewing on VR headset)

### 360° Videos
- Standard video playback controls
- Interactive 360° viewing while playing
- Click and drag to change viewing angle
- Mobile: Touch and drag or device orientation
- VR: Head tracking support with cardboard mode

## Frontend Libraries

- **Pannellum**: Used for 360° photo viewing
- **Video.js + videojs-vr**: Used for 360° video playback

## API Response Format

When 360° media is detected, the API includes additional metadata:

```json
{
  "id": "123456",
  "type": "photo",
  "url": "https://example.com/media/photo.jpg",
  "is_360": true,
  "projection_type": "equirectangular",
  "meta": {
    "original": {
      "width": 4096,
      "height": 2048,
      "aspect": 2.0
    }
  }
}
```

## Database Schema

New columns added to the `media` table:

```php
$table->boolean('is_360')->default(false);
$table->string('projection_type')->nullable();
```

## Browser Requirements

### Required for 360° Viewing:
- WebGL support
- JavaScript enabled
- Modern browser (Chrome 56+, Firefox 52+, Safari 11+, Edge 79+)

### Graceful Degradation:
- If WebGL is not supported, media displays as regular 2D image/video
- No errors shown to user
- Fallback is automatic and transparent

## Testing

Run the test suite to verify 360° detection:

```bash
php artisan test --filter=Media360DetectorTest
```

## Troubleshooting

### 360° media not detected
1. Check if `MEDIA_360_DETECTION=true` in `.env`
2. Verify XMP metadata is present: `exiftool -XMP-all media_file.jpg`
3. Check aspect ratio is 2:1 (e.g., 4096x2048, 3840x1920)

### Viewer not working
1. Check browser console for JavaScript errors
2. Verify WebGL support: Visit `https://get.webgl.org/`
3. Try a different browser or update to latest version
4. Check that Pannellum/Video.js libraries are loaded

### Performance issues
1. 360° photos/videos require more processing power
2. Recommend resolution of 4K (4096x2048) or lower
3. Enable HLS streaming for better video performance
4. Consider CDN for serving 360° media assets

## Credits

- [Pannellum](https://github.com/mpetroff/pannellum) - 360° photo viewer
- [Video.js](https://videojs.com/) - Video player
- [videojs-vr](https://github.com/videojs/videojs-vr) - VR/360° video support
- [Google Spatial Media Metadata](https://github.com/google/spatial-media) - XMP metadata format

## Future Enhancements

- Support for additional projection types (cubemap, cylindrical)
- Auto-rotation option for 360° photos
- Gyroscope support for mobile devices
- Stereoscopic 3D 360° support
- Upload UI with 360° preview
- Batch processing for adding XMP metadata
