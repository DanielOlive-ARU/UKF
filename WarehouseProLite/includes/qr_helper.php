<?php
/**
 * QR helper for WarehouseProLite label printing.
 *
 * Wraps the bundled MIT-licensed QRCode generator (Kazuhiko Arase) and produces
 * base64-encoded PNG images using GD so we can embed them inline without
 * touching disk.
 */

require_once __DIR__ . '/qr/qrcode.php';

class QrHelper
{
    /**
     * Generate a QR code data URI for the given payload.
     *
     * @param string $value Text to encode (SKU in our case)
     * @param int $targetSize Target square dimension in pixels (default 192)
     * @param int $margin Modules of white space applied around the code
     * @param int $errorLevel QR error correction level constant
     * @return string|null Base64 PNG data URI or null when GD is unavailable/fails
     */
    public static function dataUri(string $value, int $targetSize = 192, int $margin = 4, int $errorLevel = QR_ERROR_CORRECT_LEVEL_Q): ?string
    {
        if ($value === '') {
            return null;
        }

        if (!extension_loaded('gd')) {
            return null;
        }

        $qr = QRCode::getMinimumQRCode($value, $errorLevel);
        $moduleCount = $qr->getModuleCount();
        if ($moduleCount <= 0) {
            return null;
        }

        $availablePixels = max(1, $targetSize - ($margin * 2));
        $modulePixelSize = (int)floor($availablePixels / $moduleCount);
        if ($modulePixelSize < 1) {
            $modulePixelSize = 1;
        }

        $image = $qr->createImage($modulePixelSize, $margin, 0x000000, 0xFFFFFF, false);
        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width !== $targetSize || $height !== $targetSize) {
            $resized = imagecreatetruecolor($targetSize, $targetSize);
            imagefilledrectangle($resized, 0, 0, $targetSize, $targetSize, imagecolorallocate($resized, 255, 255, 255));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetSize, $targetSize, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        if ($pngData === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($pngData);
    }
}
