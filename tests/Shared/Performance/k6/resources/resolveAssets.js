let manifest;
try {
    manifest = JSON.parse(open('./manifest.json'));
} catch (err) {
    throw new Error(`Failed to load or parse manifest.json: ${err.message}`);
}

export function resolveAssets(logicalNames, baseUrl) {
    return logicalNames.map(name => {
        if (!(name in manifest)) {
            throw new Error(`Asset not found in manifest: ${name}`);
        }
        return baseUrl + manifest[name];
    });
}

// Optionally, a helper to build all requests:
export function buildRequestUrls({ baseUrl, pages = [], assetNames = [] }) {
    const assetUrls = resolveAssets(assetNames, baseUrl);
    return [
        ...pages.map(path => baseUrl + path),
        ...assetUrls
    ];
}
