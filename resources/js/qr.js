// Renders interactive QR codes with high error correction and embedded logo in the center.
import QRCode from 'qrcode';

function renderQrCanvas(canvas) {
    const url = canvas.dataset.url || '';
    if (!url) return;

    const width = parseInt(canvas.getAttribute('width') || canvas.offsetWidth || '220', 10);
    const logoSrc = canvas.dataset.logo || '/images/booktala-mark.png';

    QRCode.toCanvas(canvas, url, {
        width: width,
        margin: 1,
        errorCorrectionLevel: 'H', // High error correction permits up to 30% obstruction
        color: {
            dark: '#0B2D5B',
            light: '#FFFFFF',
        },
    }, (err) => {
        if (err || !logoSrc) return;

        const ctx = canvas.getContext('2d');
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const qrSize = canvas.width;
            const logoW = Math.round(qrSize * 0.22);
            const logoH = Math.round(logoW * (img.height / img.width));
            const badgeW = logoW + 8;
            const badgeH = logoH + 8;
            const badgeX = Math.round((qrSize - badgeW) / 2);
            const badgeY = Math.round((qrSize - badgeH) / 2);

            // Draw crisp white backing badge with border in center
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(badgeX, badgeY, badgeW, badgeH);

            ctx.strokeStyle = '#0B2D5B';
            ctx.lineWidth = 1.5;
            ctx.strokeRect(badgeX, badgeY, badgeW, badgeH);

            // Draw centered logo mark
            const imgX = badgeX + 4;
            const imgY = badgeY + 4;
            ctx.drawImage(img, imgX, imgY, logoW, logoH);
        };
        img.src = logoSrc;
    });
}

document.querySelectorAll('canvas.js-qr').forEach(renderQrCanvas);
