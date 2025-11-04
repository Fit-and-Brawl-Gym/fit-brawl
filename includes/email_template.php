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

    // Try to embed header-title and footer-title images as well (so emails don't rely on remote localhost URLs)
    $headerTitleCid = null;
    $headerTitleCid = null;
    $footerTitleCid = null;
    // Prefer a PNG for email clients (best). Try header-title.png, then header-title.svg, then footer-title.png as final fallback.
    $headerTitlePath = __DIR__ . '/../images/header-title.png';
    if (!file_exists($headerTitlePath)) $headerTitlePath = __DIR__ . '/../images/header-title.svg';
    if (!file_exists($headerTitlePath)) $headerTitlePath = __DIR__ . '/../images/footer-title.png';
    if (file_exists($headerTitlePath)) {
        try {
            $htBasename = basename($headerTitlePath);
            $htExt = strtolower(pathinfo($headerTitlePath, PATHINFO_EXTENSION));
            $htMime = $htExt === 'png' ? 'image/png' : ($htExt === 'svg' ? 'image/svg+xml' : mime_content_type($headerTitlePath));
            $mail->addEmbeddedImage($headerTitlePath, 'header_title_cid', $htBasename, 'base64', $htMime);
            $headerTitleCid = 'header_title_cid';
        } catch (\Exception $e) {
            error_log('applyEmailTemplate header title embed failed: ' . $e->getMessage());
            $headerTitleCid = null;
        }
    }

    $footerTitlePath = __DIR__ . '/../images/footer-title.png';
    if (!file_exists($footerTitlePath)) $footerTitlePath = __DIR__ . '/../images/footer-title.svg';
    if (file_exists($footerTitlePath)) {
        try {
            $ftBasename = basename($footerTitlePath);
            $ftExt = strtolower(pathinfo($footerTitlePath, PATHINFO_EXTENSION));
            $ftMime = $ftExt === 'png' ? 'image/png' : ($ftExt === 'svg' ? 'image/svg+xml' : mime_content_type($footerTitlePath));
            $mail->addEmbeddedImage($footerTitlePath, 'footer_title_cid', $ftBasename, 'base64', $ftMime);
            $footerTitleCid = 'footer_title_cid';
        } catch (\Exception $e) {
            error_log('applyEmailTemplate footer title embed failed: ' . $e->getMessage());
            $footerTitleCid = null;
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
    $bannerPath = __DIR__ . '/../images/email-banner.png';
    if (!file_exists($bannerPath)) $bannerPath = __DIR__ . '/../images/email-banner.jpg';
    if (file_exists($bannerPath)) {
        // Try to embed banner if possible
        $bannerCid = null;
        try {
            $bnBasename = basename($bannerPath);
            $bnExt = strtolower(pathinfo($bannerPath, PATHINFO_EXTENSION));
            $bnMime = $bnExt === 'png' ? 'image/png' : ($bnExt === 'jpg' || $bnExt === 'jpeg' ? 'image/jpeg' : mime_content_type($bannerPath));
            // use a deterministic cid name
            $mail->addEmbeddedImage($bannerPath, 'email_banner_cid', $bnBasename, 'base64', $bnMime);
            $bannerCid = 'email_banner_cid';
        } catch (\Exception $e) {
            error_log('applyEmailTemplate banner embed failed: ' . $e->getMessage());
            $bannerCid = null;
        }
        if ($bannerCid) {
            // Render banner as a centered boxed element (not full-bleed)
            $html .= '<tr><td align="center" style="padding:0">';
            $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto;background:' . $headerBg . ';padding:0;border-radius:0">';
            $html .= '<tr><td style="padding:0">';
            // limit banner width slightly to fit box and appear less dominant
            // center the banner image and avoid stretching so the logo inside can be centered
            $html .= '<img src="cid:' . htmlspecialchars($bannerCid) . '" alt="Header" style="display:block;margin:0 auto;width:auto;max-width:560px;height:auto;image-rendering:auto;-webkit-filter:none;filter:none;">';
            $html .= '</td></tr></table></td></tr>';
        }
    }

    // Header: boxed dark container centered (does not span full email width)
    $html .= '<tr><td align="center" style="padding:18px 0">';
    // boxed header: add bgcolor and stronger inline background-color to resist client dark-mode
    $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" bgcolor="' . $headerBg . '" style="max-width:600px;margin:0 auto;background-color:' . $headerBg . ' !important;padding:14px 18px;border-radius:4px;text-align:center;">';
    $html .= '<tr><td align="center" valign="middle" style="vertical-align:middle;text-align:center;">';
    // Center the header-title (site name) within the dark box.
    // Use the same decision we made earlier when attempting to embed the header title.
    // If we successfully embedded it, reference the CID. Otherwise, prefer an existing SVG, then PNG, as a remote src.
    if (!empty($headerTitleCid)) {
        $html .= '<img src="cid:' . htmlspecialchars($headerTitleCid) . '" alt="FitXBrawl" style="display:block;margin:0 auto;height:auto;max-width:260px;image-rendering:auto;-webkit-filter:none;filter:none;">';
    } else {
        // determine which file exists for a remote fallback (prefer svg)
        $remoteHeader = null;
        if (file_exists(__DIR__ . '/../images/header-title.svg')) {
            $remoteHeader = 'header-title.svg';
        } elseif (file_exists(__DIR__ . '/../images/header-title.png')) {
            $remoteHeader = 'header-title.png';
        } elseif (file_exists(__DIR__ . '/../images/footer-title.png')) {
            // final fallback to footer title PNG if header image is missing
            $remoteHeader = 'footer-title.png';
        }
        if ($remoteHeader) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $html .= '<img src="' . $scheme . '://' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '/fit-brawl/images/' . rawurlencode($remoteHeader) . '" alt="FitXBrawl" style="display:block;margin:0 auto;height:auto;max-width:260px;image-rendering:auto;-webkit-filter:none;filter:none;">';
        }
    }
    $html .= '</td></tr>';
    $html .= '</table>';
    $html .= '</td></tr>';
    // Body
    $html .= '<tr><td align="center" style="background-color:#ffffff;padding:28px 0" bgcolor="#ffffff">';
    $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:0 auto"><tr><td class="content" style="background-color:#ffffff !important;padding:22px;border-radius:4px;color:#111111 !important;" bgcolor="#ffffff">';
    $html .= $innerHtml;
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    // Footer
    // Footer - compact boxed dark container centered
    $html .= '<tr><td align="center" style="padding:10px 0">';
    $html .= '<table width="600" cellpadding="0" cellspacing="0" role="presentation" bgcolor="' . $footerBg . '" style="max-width:600px;margin:0 auto;background-color:' . $footerBg . ' !important;padding:10px 18px;border-radius:4px"><tr><td align="center" style="font-size:13px;line-height:18px;color:' . $textColor . '">';
    // try to use footer-title image if available for the footer heading
    $footerTitlePath = __DIR__ . '/../images/footer-title.png';
    if (file_exists($footerTitlePath)) {
        if (!empty($footerTitleCid)) {
            $html .= '<div style="margin-bottom:4px"><img src="cid:' . htmlspecialchars($footerTitleCid) . '" alt="FITXBRAWL" style="height:auto;max-width:120px; display:block; margin:0 auto;image-rendering:auto;-webkit-filter:none;filter:none;"></div>';
        } else {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $html .= '<div style="margin-bottom:4px"><img src="' . $scheme . '://' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '/fit-brawl/images/' . rawurlencode(basename($footerTitlePath)) . '" alt="FITXBRAWL" style="height:auto;max-width:120px; display:block; margin:0 auto"></div>';
        }
    } else {
        $html .= '<div style="font-weight:600;margin-bottom:4px">' . htmlspecialchars('FITXBRAWL') . '</div>';
    }

    // compact address lines with tighter line-height
    $html .= '<div style="margin:0;padding:0;font-size:13px;line-height:18px;color:' . $textColor . ';">' . nl2br(htmlspecialchars($opts['footerAddress'])) . '</div>';
    $html .= '<div style="margin-top:6px;font-size:13px;line-height:18px;color:' . $textColor . ';">Gmail: <a href="mailto:' . htmlspecialchars($opts['footerEmail']) . '" style="color:' . $textColor . ';text-decoration:underline">' . htmlspecialchars($opts['footerEmail']) . '</a></div>';
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
