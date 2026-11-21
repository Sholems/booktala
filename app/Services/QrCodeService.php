<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generates a QR code image with the BookTala logo embedded in the center.
     * Returns a base64 Data URI string (data:image/png;base64,...).
     */
    public function generateReferralQrDataUri(string $url, ?string $logoPath = null): string
    {
        $rawPng = $this->generateReferralQrPng($url, $logoPath);

        return 'data:image/png;base64,' . base64_encode($rawPng);
    }

    /**
     * Generates raw PNG binary of the QR code with embedded logo.
     */
    public function generateReferralQrPng(string $url, ?string $logoPath = null): string
    {
        $options = new QROptions([
            'version' => 7,
            'eccLevel' => EccLevel::H, // High error correction allows 30% obstruction
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 10,
        ]);

        $qrcode = new QRCode($options);
        $qrBinary = $qrcode->render($url);

        $im = imagecreatefromstring($qrBinary);
        if (! $im) {
            return $qrBinary;
        }

        $logoFile = $logoPath ?: public_path('images/booktala-mark.png');
        if (file_exists($logoFile)) {
            $logo = @imagecreatefrompng($logoFile);
            if ($logo) {
                $qrWidth = imagesx($im);
                $qrHeight = imagesy($im);
                $logoW = imagesx($logo);
                $logoH = imagesy($logo);

                // Center logo size: ~22% of QR width
                $targetLogoW = (int) round($qrWidth * 0.22);
                $targetLogoH = (int) round($targetLogoW * ($logoH / $logoW));

                $badgePad = 12;
                $badgeW = $targetLogoW + $badgePad;
                $badgeH = $targetLogoH + $badgePad;
                $badgeX = (int) round(($qrWidth - $badgeW) / 2);
                $badgeY = (int) round(($qrHeight - $badgeH) / 2);

                // White badge background with navy border
                $white = imagecolorallocate($im, 255, 255, 255);
                $navy = imagecolorallocate($im, 11, 45, 91); // #0B2D5B

                imagefilledrectangle($im, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $white);
                imagerectangle($im, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $navy);

                $logoX = $badgeX + (int) round($badgePad / 2);
                $logoY = $badgeY + (int) round($badgePad / 2);

                imagecopyresampled($im, $logo, $logoX, $logoY, 0, 0, $targetLogoW, $targetLogoH, $logoW, $logoH);
                imagedestroy($logo);
            }
        }

        ob_start();
        imagepng($im);
        $finalPng = ob_get_clean();
        imagedestroy($im);

        return $finalPng ?: $qrBinary;
    }
}
