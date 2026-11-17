// Renders QR codes for printable patrol checkpoint sheets.
import QRCode from 'qrcode';

document.querySelectorAll('canvas.js-qr').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.url, { width: 220, margin: 1, errorCorrectionLevel: 'M' });
});
