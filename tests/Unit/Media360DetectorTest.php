<?php

use App\Util\Media\Media360Detector;

test('detect 360 photo from aspect ratio', function () {
    // Test 2:1 aspect ratio detection (typical for equirectangular)
    $result = Media360Detector::detect('/fake/path.jpg', 4096, 2048);
    
    expect($result['is_360'])->toBeTrue();
    expect($result['projection_type'])->toBe('equirectangular');
});

test('detect non-360 photo from normal aspect ratio', function () {
    // Test 4:3 aspect ratio (not 360)
    $result = Media360Detector::detect('/fake/path.jpg', 4000, 3000);
    
    expect($result['is_360'])->toBeFalse();
    expect($result['projection_type'])->toBeNull();
});

test('detect non-360 photo from 16:9 aspect ratio', function () {
    // Test 16:9 aspect ratio (common video/photo format, not 360)
    $result = Media360Detector::detect('/fake/path.jpg', 1920, 1080);
    
    expect($result['is_360'])->toBeFalse();
    expect($result['projection_type'])->toBeNull();
});

test('detect 360 from aspect ratio with tolerance', function () {
    // Test aspect ratio within tolerance (1.98:1)
    $result = Media360Detector::detect('/fake/path.jpg', 3960, 2000);
    
    expect($result['is_360'])->toBeTrue();
    expect($result['projection_type'])->toBe('equirectangular');
});

test('aspect ratio outside tolerance is not detected as 360', function () {
    // Test aspect ratio outside tolerance (2.1:1)
    $result = Media360Detector::detect('/fake/path.jpg', 4200, 2000);
    
    expect($result['is_360'])->toBeFalse();
    expect($result['projection_type'])->toBeNull();
});

test('handles missing dimensions gracefully', function () {
    // Test without dimensions
    $result = Media360Detector::detect('/fake/path.jpg', null, null);
    
    expect($result['is_360'])->toBeFalse();
    expect($result['projection_type'])->toBeNull();
});

test('handles zero height gracefully', function () {
    // Test with zero height
    $result = Media360Detector::detect('/fake/path.jpg', 4000, 0);
    
    expect($result['is_360'])->toBeFalse();
    expect($result['projection_type'])->toBeNull();
});
