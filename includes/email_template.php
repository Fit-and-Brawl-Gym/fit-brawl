<?php
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Apply a standard HTML email template (dark header + footer) to a message.
 * Embeds a logo using CID if requested and sets Body and AltBody on the PHPMailer instance.
 *
 * @param PHPMailer $mail The PHPMailer instance (already configured)
 * @param string $innerHtml The HTML content for the email body (already escaped where needed)
 * @param array $opts Options:
 *   - logoPath: filesystem path to the logo to embed (string)
 *   - embedLogo: bool whether to embed logo as CID (default true)
 *   - logoCid: CID to use for embedded logo (default 'logo_cid')
 *   - footerEmail: contact email to show in footer
 *   - footerAddress: postal address to show in footer
 *
 * @return void
 */
function applyEmailTemplate(PHPMailer $mail, string $innerHtml, array $opts = []) {
    // Allow overriding footer from environment variables
    $defaultFooterEmail = getenv('FOOTER_EMAIL') ?: (getenv('EMAIL_USER') ?: 'fitxbrawl@gmail.com');
    $defaultFooterAddress = getenv('FOOTER_ADDRESS') ?: "1832 Oroquieta Rd, Santa Cruz, Manila,\n1008 Metro Manila";

    $opts = array_merge([
        'logoPath' => __DIR__ . '/../images/fnb-logo-yellow.svg',
        'embedLogo' => true,
        'logoCid' => 'logo_cid',
        'footerEmail' => $defaultFooterEmail,
        'footerAddress' => $defaultFooterAddress,
    ], $opts);

    // Prefer a PNG logo when available for better compatibility in email clients
    $preferredLogo = $opts['logoPath'];
    $alternateLogo = null;
    if (preg_match('/\.svg$/i', $preferredLogo)) {
        $pngCandidate = preg_replace('/\.svg$/i', '.png', $preferredLogo);
        if (file_exists($pngCandidate)) {
            $preferredLogo = $pngCandidate;
        } else {
            // if no png exists, remember svg as alternate
            $alternateLogo = $preferredLogo;
        }
    } else {
        // if provided path isn't svg, also check svg variant as alternate
        $svgCandidate = preg_replace('/\.[^.]+$/', '.svg', $preferredLogo);
        if (file_exists($svgCandidate)) {
            $alternateLogo = $svgCandidate;
        }
    }

    // We intentionally do not display the yellow logo in emails (requested).
    // Leave logo embedding out to keep the header clean and compact.
    $logoCid = null;
    $embedPath = null;

    // Only use explicit mail-header.png / mail-footer.png as header/footer banners.
    // Legacy header-title/footer-title fallbacks have been removed per request.
    $mailHeaderCid = null;
    $mailFooterCid = null;

    // Prefer an explicit mail header banner if provided (more robust for dark-mode)
    $mailHeaderPath = __DIR__ . '/../images/mail-header.png';
    if (!file_exists($mailHeaderPath)) $mailHeaderPath = __DIR__ . '/../images/mail-header.jpg';
    if (file_exists($mailHeaderPath)) {
        try {
            $mhBasename = basename($mailHeaderPath);
            $mhExt = strtolower(pathinfo($mailHeaderPath, PATHINFO_EXTENSION));
            $mhMime = $mhExt === 'png' ? 'image/png' : ($mhExt === 'jpg' || $mhExt === 'jpeg' ? 'image/jpeg' : mime_content_type($mailHeaderPath));
            $mail->addEmbeddedImage($mailHeaderPath, 'mail_header_cid', $mhBasename, 'base64', $mhMime);
            $mailHeaderCid = 'mail_header_cid';
        } catch (\Exception $e) {
            error_log('applyEmailTemplate mail header embed failed: ' . $e->getMessage());
            $mailHeaderCid = null;
        }
    }

    // Prefer an explicit mail footer banner if provided
    $mailFooterPath = __DIR__ . '/../images/mail-footer.png';
    if (!file_exists($mailFooterPath)) $mailFooterPath = __DIR__ . '/../images/mail-footer.jpg';
    if (file_exists($mailFooterPath)) {
        try {
            $mfBasename = basename($mailFooterPath);
            $mfExt = strtolower(pathinfo($mailFooterPath, PATHINFO_EXTENSION));
            $mfMime = $mfExt === 'png' ? 'image/png' : ($mfExt === 'jpg' || $mfExt === 'jpeg' ? 'image/jpeg' : mime_content_type($mailFooterPath));
            $mail->addEmbeddedImage($mailFooterPath, 'mail_footer_cid', $mfBasename, 'base64', $mfMime);
            $mailFooterCid = 'mail_footer_cid';
        } catch (\Exception $e) {
            error_log('applyEmailTemplate mail footer embed failed: ' . $e->getMessage());
            $mailFooterCid = null;
        }
    }

    // Build the HTML wrapper (table-based for client compatibility)
    $headerBg = '#111827'; // dark
    $footerBg = '#111827';
    $textColor = '#ffffff';

    // No yellow logo displayed; header will use a centered header-title or banner instead.
    $logoImgTag = '';

    $html = '';
    $html .= '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    // Prefer light color-scheme where supported to reduce client dark-mode transformations
    $html .= '<meta name="color-scheme" content="light">';
    $html .= '<meta name="supported-color-schemes" content="light">';
    // Inline-friendly CSS: keep minimal but make content responsive and force light backgrounds where possible
    $html .= '<style>:root{color-scheme: light;}body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#ffffff !important;color:#111111 !important;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}.content{padding:20px 18px;color:#111}.mobile-stack{display:block}</style>';
    $html .= '</head><body style="background-color:#ffffff !important;color:#111111 !important;">';

    // Outer table
    // outer table uses explicit bgcolor and inline background-color to discourage dark-mode overrides
    $html .= '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#ffffff" style="background-color:#ffffff !important;">';

    // Header - mimic site header: logo (and optional header title image if available)
    // If a banner image exists, use it as a full-width header banner for a stronger visual (preferred)
    // If an explicit mail-header image was embedded use that first, otherwise fall back to email-banner
    if (!empty($mailHeaderCid)) {
        $bannerCid = $mailHeaderCid;
    } else {
        // No fallback banner: only explicit mail-header.png is used for the header per request
        $bannerCid = null;
    }
    if (!empty($bannerCid)) {
        // Render banner as a centered element with no extra cell padding or borders
        // to avoid mail clients (Gmail mobile) adding a white card/outline.
        $html .= '<tr><td align="center" style="padding:0;line-height:0;border-collapse:collapse">';
        $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto;padding:0;border-collapse:collapse;border-spacing:0;background:' . $headerBg . ';">';
        $html .= '<tr><td style="padding:0;line-height:0">';
    // Use full-width responsive image and remove borders/outlines; expand max-width to full 600px so desktop header fills the content box
    $html .= '<img src="cid:' . htmlspecialchars($bannerCid) . '" alt="Header" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:100%;max-width:600px;height:auto;line-height:0;-webkit-filter:none;filter:none;">';
        $html .= '</td></tr></table></td></tr>';
    }

    // (Removed boxed site-title header; only the mail-header banner will be used)
    // Body
    // reduce bottom padding so footer sits directly after content (helps mobile clients)
    $html .= '<tr><td align="center" style="background-color:#ffffff;padding:18px 0 0" bgcolor="#ffffff">';
    $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto"><tr><td class="content" style="background-color:#ffffff !important;padding:22px;border-radius:4px;color:#111111 !important;" bgcolor="#ffffff">';
    $html .= $innerHtml;
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    // Footer
    // Footer - if a mail-footer image exists, render it cleanly with no extra box to avoid white outlines
    if (!empty($mailFooterCid)) {
        // remove top padding so footer sits flush with above content
        $html .= '<tr><td align="center" style="padding:0;line-height:0;border-collapse:collapse">';
        $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto;padding:0;border-collapse:collapse;border-spacing:0;">';
        $html .= '<tr><td style="padding:0;line-height:0;text-align:center">';
    $html .= '<img src="cid:' . htmlspecialchars($mailFooterCid) . '" alt="FITXBRAWL" style="display:block;border:0;outline:none;text-decoration:none;width:100%;max-width:600px;height:auto;line-height:0;">';
        $html .= '</td></tr></table></td></tr>';
    } else {
        // No mail-footer image; show a simple textual brand heading
        $html .= '<tr><td align="center" style="padding:10px 0"><table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto"><tr><td align="center" style="font-size:13px;line-height:18px;color:' . $textColor . '">';
        $html .= '<div style="font-weight:600;margin-bottom:4px">' . htmlspecialchars('FITXBRAWL') . '</div>';
        $html .= '</td></tr></table></td></tr>';
    }

    // Footer contact lines removed — footer will be image-only when mail-footer.png is provided
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    $html .= '</table>';
    $html .= '</body></html>';

    $mail->Body = $html;

    // Plain text alt body
    $alt = strip_tags(preg_replace('/\s+/', ' ', $innerHtml));
    $alt .= "\n\n" . $opts['footerAddress'] . "\n" . $opts['footerEmail'];
    $mail->AltBody = $alt;
}

?>
