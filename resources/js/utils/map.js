/*
 * Turns what the admin pasted (a Google Maps link, the "Embed a map" code, coordinates, or nothing)
 * into a map that can be shown in an <iframe>, without a Google API key.
 */
export function mapEmbedUrl(location, address) {
    const raw = String(location || "").trim();

    // The full <iframe ...> code from Google Maps › Share › Embed a map
    // Only real Google Maps embeds are used, so no other website can be framed on the page.
    const isGoogleEmbed = (url) => /^https:\/\/(www\.)?google\.com\/maps\/embed\?/.test(url);
    const src = raw.match(/src="([^"]+)"/);
    if (src && isGoogleEmbed(src[1])) return src[1];
    if (isGoogleEmbed(raw)) return raw;

    // A normal Google Maps link with coordinates in it: .../@23.7509,90.3935,17z
    const at = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (at) return `https://maps.google.com/maps?q=${at[1]},${at[2]}&z=16&output=embed`;

    // Plain coordinates: "23.7509, 90.3935"
    const ll = raw.match(/^(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)$/);
    if (ll) return `https://maps.google.com/maps?q=${ll[1]},${ll[2]}&z=16&output=embed`;

    // Otherwise search for the place name or the store address
    const query = raw && !/^https?:/.test(raw) ? raw : address;
    return query ? `https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=16&output=embed` : "";
}

/** "Get directions" link that opens Google Maps (app on phones). */
export function directionsUrl(location, address) {
    const raw = String(location || "").trim();
    const at = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/) || raw.match(/^(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)$/);
    const destination = at ? `${at[1]},${at[2]}` : raw && !/^https?:|<iframe/.test(raw) ? raw : address;
    return destination ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}` : "";
}
