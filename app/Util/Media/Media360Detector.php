<?php

namespace App\Util\Media;

use Illuminate\Support\Facades\Log;

class Media360Detector
{
    /**
     * Detect if media is 360° by reading XMP metadata or analyzing aspect ratio
     *
     * @param string $filePath Path to media file
     * @param int|null $width Image/video width
     * @param int|null $height Image/video height
     * @return array ['is_360' => bool, 'projection_type' => string|null]
     */
    public static function detect($filePath, $width = null, $height = null)
    {
        $result = [
            'is_360' => false,
            'projection_type' => null,
        ];

        // First, try to detect from XMP metadata
        try {
            $xmpData = self::extractXmpData($filePath);
            
            if ($xmpData) {
                // Check for various 360° indicators in XMP
                $projection = $xmpData['projection_type'] ?? null;
                $spherical = $xmpData['spherical'] ?? null;
                
                if ($projection === 'equirectangular' || $spherical === 'true') {
                    $result['is_360'] = true;
                    $result['projection_type'] = 'equirectangular';
                    return $result;
                }
            }
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::info('XMP extraction failed: '.$e->getMessage());
            }
        }

        // Fallback: Check 2:1 aspect ratio as a heuristic
        // This is not reliable but can catch some cases
        if ($width && $height && $height > 0) {
            $aspectRatio = $width / $height;
            // Check if aspect ratio is approximately 2:1 (with some tolerance)
            if ($aspectRatio >= 1.95 && $aspectRatio <= 2.05) {
                $result['is_360'] = true;
                $result['projection_type'] = 'equirectangular';
            }
        }

        return $result;
    }

    /**
     * Extract XMP data from media file
     *
     * @param string $filePath
     * @return array|null
     */
    private static function extractXmpData($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $xmpData = [];

        // Look for XMP data in the file
        // XMP data is typically enclosed in <x:xmpmeta> tags
        if (preg_match('/<x:xmpmeta[\s\S]*?<\/x:xmpmeta>/i', $content, $matches)) {
            $xmp = $matches[0];

            // Check for ProjectionType
            if (preg_match('/<GPano:ProjectionType>(.*?)<\/GPano:ProjectionType>/i', $xmp, $projMatch)) {
                $xmpData['projection_type'] = strtolower(trim($projMatch[1]));
            }

            // Check for Spherical metadata (Google Spatial Media format)
            if (preg_match('/<GSpherical:Spherical>(.*?)<\/GSpherical:Spherical>/i', $xmp, $sphericalMatch)) {
                $xmpData['spherical'] = strtolower(trim($sphericalMatch[1]));
            }

            // Alternative attribute format
            if (preg_match('/GPano:ProjectionType="(.*?)"/i', $xmp, $projMatch)) {
                $xmpData['projection_type'] = strtolower(trim($projMatch[1]));
            }

            if (preg_match('/GSpherical:Spherical="(.*?)"/i', $xmp, $sphericalMatch)) {
                $xmpData['spherical'] = strtolower(trim($sphericalMatch[1]));
            }
        }

        return empty($xmpData) ? null : $xmpData;
    }

    /**
     * Check if exiftool is available on the system
     *
     * @return bool
     */
    public static function isExiftoolAvailable()
    {
        static $available = null;

        if ($available === null) {
            $output = [];
            $returnVar = 0;
            @exec('exiftool -ver 2>&1', $output, $returnVar);
            $available = ($returnVar === 0);
        }

        return $available;
    }

    /**
     * Extract XMP data using exiftool if available
     *
     * @param string $filePath
     * @return array|null
     */
    public static function extractXmpWithExiftool($filePath)
    {
        if (!self::isExiftoolAvailable() || !file_exists($filePath)) {
            return null;
        }

        $xmpData = [];
        $output = [];
        $command = sprintf(
            'exiftool -XMP-GPano:ProjectionType -XMP-GSpherical:Spherical -json %s',
            escapeshellarg($filePath)
        );

        @exec($command, $output, $returnVar);

        if ($returnVar === 0 && !empty($output)) {
            $json = implode('', $output);
            $data = json_decode($json, true);

            if ($data && is_array($data) && count($data) > 0) {
                $firstItem = $data[0];
                
                if (isset($firstItem['ProjectionType'])) {
                    $xmpData['projection_type'] = strtolower($firstItem['ProjectionType']);
                }

                if (isset($firstItem['Spherical'])) {
                    $xmpData['spherical'] = strtolower($firstItem['Spherical']);
                }
            }
        }

        return empty($xmpData) ? null : $xmpData;
    }
}
